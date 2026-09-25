<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestMessage;
use App\Game\Automation\Delve\Events\DelveStatusUpdated;
use App\Game\Automation\Exploration\Services\ExplorationLogService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardProgressionUpdated;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Types\CharacterMessageTypes;
use Closure;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BattleRewardPresentationService
{
    use SafelyBroadcastsEvents;

    /**
     * @param BattleRewardLedgerService $battleRewardLedgerService
     * @param BattleRewardLiveUpdateService $battleRewardLiveUpdateService
     * @param BattleRewardMessageOutboxService $battleRewardMessageOutboxService
     * @param ExplorationLogService $explorationLogService
     * @param ServerMessageBuilder $serverMessageBuilder
     */
    public function __construct(
        private readonly BattleRewardLedgerService $battleRewardLedgerService,
        private readonly BattleRewardLiveUpdateService $battleRewardLiveUpdateService,
        private readonly BattleRewardMessageOutboxService $battleRewardMessageOutboxService,
        private readonly ExplorationLogService $explorationLogService,
        private readonly ServerMessageBuilder $serverMessageBuilder,
    ) {}

    /**
     * Present a completed reward request to the player: publish the final player updates, then emit its outbox messages in order.
     *
     * @param CharacterBattleRewardRequest $request
     * @return void
     */
    public function present(CharacterBattleRewardRequest $request): void
    {
        $this->runFinalPlayerUpdatesStep($request);

        $this->runMessageOutboxStep($request);
    }

    /**
     * Run the request's FINAL_PLAYER_UPDATES ledger step, publishing the authoritative live update and any source-specific update.
     *
     * @param CharacterBattleRewardRequest $request
     * @return void
     */
    private function runFinalPlayerUpdatesStep(CharacterBattleRewardRequest $request): void
    {
        $step = $request->steps()
            ->where('step_name', BattleRewardStepName::FINAL_PLAYER_UPDATES)
            ->first();

        if (is_null($step)) {
            throw new RuntimeException(
                'Reward request '.$request->id.' has no FINAL_PLAYER_UPDATES ledger row. The ledger is incomplete.',
            );
        }

        if ($step->status === BattleRewardStepStatus::COMPLETED) {
            $this->battleRewardLedgerService->log('step.skipped_completed', $request, $step);

            return;
        }

        $step = $this->battleRewardLedgerService->startStep($step);

        try {
            $this->dispatchFinalPlayerUpdates($request);
        } catch (Throwable $throwable) {
            $this->battleRewardLedgerService->failStep($step, $throwable);

            throw $throwable;
        }

        $this->battleRewardLedgerService->completeStep($step);
    }

    /**
     * Publish the authoritative live player update, plus the Exploration output or Delve status update for those sources.
     *
     * @param CharacterBattleRewardRequest $request
     * @return void
     */
    private function dispatchFinalPlayerUpdates(CharacterBattleRewardRequest $request): void
    {
        $character = Character::find($request->character_id);

        if (is_null($character)) {
            return;
        }

        Log::channel('reward_processing')->debug('Authoritative live reward update attempted.', [
            'character_id' => $character->id,
            'request_id' => $request->id,
        ]);

        $this->battleRewardLiveUpdateService->broadcast($character->id);

        if ($request->source_type === BattleRewardRequestSourceType::EXPLORATION) {
            $this->refreshExplorationOutput($character);
        }

        if ($request->source_type === BattleRewardRequestSourceType::AUTOMATION) {
            $this->safelyDispatchBroadcastEvent(
                new DelveStatusUpdated($character->user_id),
                ['character_id' => $character->id]
            );
        }
    }

    /**
     * Refresh the Character's Exploration output, logging instead of failing the presentation when the refresh cannot be sent.
     *
     * @param Character $character
     * @return void
     */
    private function refreshExplorationOutput(Character $character): void
    {
        try {
            $this->explorationLogService->outputForCharacter($character);
        } catch (Throwable $throwable) {
            Log::channel('reward_processing')->warning('Exploration output update failed. Reward row will not be marked failed.', [
                'character_id' => $character->id,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);

            Log::warning('Unable to dispatch exploration reward queue update.', [
                'character_id' => $character->id,
                'exception_class' => $throwable::class,
                'exception' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Run the request's MESSAGE_OUTBOX ledger step, emitting every unemitted message and the matching XP progression snapshot before each level up message.
     *
     * @param CharacterBattleRewardRequest $request
     * @return void
     */
    private function runMessageOutboxStep(CharacterBattleRewardRequest $request): void
    {
        $step = $request->steps()
            ->where('step_name', BattleRewardStepName::MESSAGE_OUTBOX)
            ->firstOrFail();

        if ($step->status === BattleRewardStepStatus::COMPLETED) {
            $this->battleRewardLedgerService->log('step.skipped_completed', $request, $step);

            return;
        }

        $step = $this->battleRewardLedgerService->startStep($step);

        try {
            $emittedCount = $this->battleRewardMessageOutboxService->emitUnemittedMessages(
                $request,
                $this->progressionBroadcaster($request),
            );
        } catch (Throwable $throwable) {
            $this->battleRewardLedgerService->failStep($step, $throwable);

            throw $throwable;
        }

        $this->battleRewardLedgerService->completeStep($step, ['emitted_message_count' => $emittedCount]);
    }

    /**
     * Build the callback that broadcasts the XP progression snapshot matching a level up message immediately before that message is emitted.
     *
     * @param CharacterBattleRewardRequest $request
     * @return Closure
     */
    private function progressionBroadcaster(CharacterBattleRewardRequest $request): Closure
    {
        $progressionByMessage = $this->progressionByLevelUpMessage($request);

        return function (CharacterBattleRewardRequestMessage $message) use ($request, $progressionByMessage): void {
            if ($message->step_name !== BattleRewardStepName::XP) {
                return;
            }

            $snapshot = $progressionByMessage[$message->message] ?? null;

            if (is_null($snapshot)) {
                return;
            }

            event(new BattleRewardProgressionUpdated(
                $message->user_id,
                $request->id,
                $snapshot['level'],
                $snapshot['xp'],
                $snapshot['xp_next'],
            ));
        };
    }

    /**
     * Key the request's ordered XP progression snapshots by the exact level up message generated for each snapshot.
     *
     * @param CharacterBattleRewardRequest $request
     * @return array
     */
    private function progressionByLevelUpMessage(CharacterBattleRewardRequest $request): array
    {
        $xpStep = $request->steps()
            ->where('step_name', BattleRewardStepName::XP)
            ->first();

        $progression = $xpStep?->checkpoint_json['progression'] ?? [];

        if ($progression === []) {
            return [];
        }

        return array_combine(
            array_map(
                fn (array $snapshot): string => $this->serverMessageBuilder->buildWithAdditionalInformation(CharacterMessageTypes::LEVEL_UP, $snapshot['level']),
                $progression,
            ),
            $progression,
        );
    }
}
