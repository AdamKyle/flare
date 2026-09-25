<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Events\BattleRewardProgressionUpdated;
use App\Game\BattleRewardProcessing\Services\BattleRewardPresentationQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardPresentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCharacterBattleRewardPresentationQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    /**
     * @param int $characterId
     */
    public function __construct(private readonly int $characterId) {}

    /**
     * Drain the Character's completed reward requests whose presentation is unfinished, oldest first, under the Character's presentation lock, then continue when work appeared across the unlock boundary.
     *
     * @param BattleRewardPresentationQueueManager $queueManager
     * @param BattleRewardPresentationService $presentationService
     * @return void
     */
    public function handle(
        BattleRewardPresentationQueueManager $queueManager,
        BattleRewardPresentationService $presentationService,
    ): void {
        $presentationLock = $queueManager->processorLock($this->characterId);

        if (! $presentationLock->get()) {
            Log::channel('reward_processing')->debug('Presentation lock denied. Another presentation worker is draining this Character.', [
                'character_id' => $this->characterId,
            ]);

            return;
        }

        Log::channel('reward_processing')->info('Presentation job started.', [
            'character_id' => $this->characterId,
            'job_attempt' => $this->attempts(),
        ]);

        try {
            $presentedCount = $this->drainPresentationLane($queueManager, $presentationService);

            $this->clearProgressionOverlay();
        } finally {
            $presentationLock->release();
        }

        Log::channel('reward_processing')->info('Presentation job completed.', [
            'character_id' => $this->characterId,
            'presented_count' => $presentedCount,
        ]);

        $this->continueWhenWorkAppearedAcrossUnlock($queueManager);
    }

    /**
     * Dispatch a continuation presentation job when a reward completed between the final empty check and the lock release, because its own worker was denied the lock.
     *
     * @param BattleRewardPresentationQueueManager $queueManager
     * @return void
     */
    private function continueWhenWorkAppearedAcrossUnlock(BattleRewardPresentationQueueManager $queueManager): void
    {
        if (! $queueManager->hasPendingRequests($this->characterId)) {
            return;
        }

        Log::channel('reward_processing')->info('Presentation work appeared across the unlock boundary. Continuation dispatched.', [
            'character_id' => $this->characterId,
        ]);

        self::dispatch($this->characterId)
            ->onConnection('battle_reward_processing')
            ->onQueue('battle_reward_presentation');
    }

    /**
     * Present every completed reward request whose presentation is unfinished, oldest first, returning how many were presented.
     *
     * @param BattleRewardPresentationQueueManager $queueManager
     * @param BattleRewardPresentationService $presentationService
     * @return int
     */
    private function drainPresentationLane(
        BattleRewardPresentationQueueManager $queueManager,
        BattleRewardPresentationService $presentationService,
    ): int {
        $presentedCount = 0;
        $request = $queueManager->nextRequest($this->characterId);

        while (! is_null($request)) {
            $this->presentRequest($presentationService, $request);

            $presentedCount++;
            $request = $queueManager->nextRequest($this->characterId);
        }

        return $presentedCount;
    }

    /**
     * Present one completed reward request, logging the failure before letting the queue retry the job.
     *
     * @param BattleRewardPresentationService $presentationService
     * @param CharacterBattleRewardRequest $request
     * @return void
     */
    private function presentRequest(BattleRewardPresentationService $presentationService, CharacterBattleRewardRequest $request): void
    {
        try {
            $presentationService->present($request);
        } catch (Throwable $throwable) {
            Log::channel('reward_processing')->error('Presentation of a completed reward request failed. The job will be retried.', [
                'character_id' => $this->characterId,
                'request_id' => $request->id,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        Log::channel('reward_processing')->debug('Completed reward request presented.', [
            'character_id' => $this->characterId,
            'request_id' => $request->id,
        ]);
    }

    /**
     * Tell the Character's client that presentation caught up so it drops the transient progression overlay.
     *
     * @return void
     */
    private function clearProgressionOverlay(): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        event(new BattleRewardProgressionUpdated(
            $character->user_id,
            0,
            null,
            null,
            null,
            true,
        ));
    }
}
