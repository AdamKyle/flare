<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Quest;
use App\Game\Automation\Events\DelveStatusUpdated;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class ProcessCharacterBattleRewardQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SafelyBroadcastsEvents, SerializesModels;

    private const MAX_REQUESTS = 50;

    private const MAX_SECONDS = 20;

    public function __construct(private readonly int $characterId) {}

    public function handle(
        BattleRewardProcessingQueueManager $queueManager,
        BattleRewardService $battleRewardService,
        NpcQuestRewardHandler $npcQuestRewardHandler,
        GuideQuestService $guideQuestService,
    ): void {
        $startedAt = microtime(true);
        $processed = 0;
        $heartbeatCallback = fn() => $queueManager->updateHeartbeat($this->characterId);

        while ($processed < self::MAX_REQUESTS && microtime(true) - $startedAt < self::MAX_SECONDS) {
            $request = $queueManager->nextRequest($this->characterId);

            if (is_null($request)) {
                break;
            }

            try {
                $payload = $request->handler_payload;

                match ($request->source_type) {
                    BattleRewardRequestSourceType::BATTLE,
                    BattleRewardRequestSourceType::EXPLORATION,
                    BattleRewardRequestSourceType::AUTOMATION => $battleRewardService
                        ->withHeartbeatCallback($heartbeatCallback)
                        ->setUp($this->characterId, (int) $payload['monster_id'])
                        ->setContext($payload['context'] ?? [])
                        ->processRewards(true),
                    BattleRewardRequestSourceType::QUEST,
                    BattleRewardRequestSourceType::RAID_QUEST => $this->processQuestReward(
                        $npcQuestRewardHandler,
                        (int) $payload['quest_id'],
                    ),
                    BattleRewardRequestSourceType::GUIDE_QUEST => $guideQuestService
                        ->processQueuedRewards(
                            Character::findOrFail($this->characterId),
                            GuideQuest::findOrFail((int) $payload['guide_quest_id']),
                        ),
                    BattleRewardRequestSourceType::FUTURE => throw new RuntimeException(
                        'No reward processor exists for future reward requests.',
                    ),
                };

                $this->dispatchFinalPlayerUpdates($request->source_type);

                $queueManager->markCompleted($request);
            } catch (Throwable $exception) {
                $queueManager->markFailed($request, $exception);

                (new MonitoredBugReportService)->reportError(
                    'battle-reward-queue',
                    $exception->getMessage(),
                    ['character_id' => $this->characterId, 'source_type' => $request->source_type?->value ?? 'unknown'],
                    $exception::class,
                    $this->characterId,
                );
            }

            $processed++;
        }

        if ($queueManager->hasPendingRequests($this->characterId)) {
            $queueManager->updateHeartbeat($this->characterId);

            self::dispatch($this->characterId)
                ->onConnection('battle_reward_processing')
                ->onQueue('battle_reward_processing');

            return;
        }

        $markedInactive = $queueManager->markQueueInactiveIfEmpty($this->characterId);

        if (! $markedInactive) {
            self::dispatch($this->characterId)
                ->onConnection('battle_reward_processing')
                ->onQueue('battle_reward_processing');

            return;
        }

        if ($queueManager->hasPendingRequests($this->characterId)) {
            $queueManager->ensureProcessorRunning($this->characterId);
        }
    }

    private function dispatchFinalPlayerUpdates(BattleRewardRequestSourceType $sourceType): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        $this->safelyDispatchBroadcastEvent(
            new UpdateTopBarEvent($character),
            ['character_id' => $this->characterId]
        );

        if ($sourceType === BattleRewardRequestSourceType::AUTOMATION) {
            $this->safelyDispatchBroadcastEvent(
                new DelveStatusUpdated($character->user_id),
                ['character_id' => $this->characterId]
            );
        }
    }

    private function processQuestReward(
        NpcQuestRewardHandler $npcQuestRewardHandler,
        int $questId,
    ): void {
        $character = Character::findOrFail($this->characterId);
        $quest = Quest::findOrFail($questId);

        $npcQuestRewardHandler->processReward($quest, $quest->npc, $character);

        event(new GlobalMessageEvent(
            $character->name . ' Has completed a quest (' . $quest->name . ') for: '
            . $quest->npc->real_name . ' and been rewarded with a godly gift!',
        ));
    }
}
