<?php

namespace App\Game\Battle\Handlers;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Monster;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\CharacterRevive;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class BattleEventHandler
{
    use FetchEquipped;

    /**
     * @param BattleRewardProcessingQueueManager $battleRewardProcessingQueueManager
     * @param WeeklyBattleService $weeklyBattleService
     * @param BatchCraftingAutomationService $batchCraftingAutomationService
     * @param MonitoredBugReportService $monitoredBugReportService
     */
    public function __construct(
        private BattleRewardProcessingQueueManager $battleRewardProcessingQueueManager,
        private WeeklyBattleService $weeklyBattleService,
        private BatchCraftingAutomationService $batchCraftingAutomationService,
        private readonly MonitoredBugReportService $monitoredBugReportService,
    ) {}

    /**
     * Process the fact the character has died.
     *
     * @param Character $character
     * @param ?Monster $monster
     * @return void
     */
    public function processDeadCharacter(Character $character, ?Monster $monster = null): void
    {
        $updatedRows = Character::where('id', $character->id)
            ->where('is_dead', false)
            ->update(['is_dead' => true]);

        if ($updatedRows === 0) {
            return;
        }

        $character = $character->refresh();

        $this->batchCraftingAutomationService->completeForDeath($character);

        if (! is_null($monster)) {

            if (! is_null($monster->only_for_location_type)) {
                $this->weeklyBattleService->handleCharacterDeath($character, $monster);
            }
        }

        event(new AttackTimeOutEvent($character));

        event(new ServerMessageEvent($character->user, 'You are dead. Please revive yourself by clicking revive.'));
        event(new UpdateCharacterStatus($character));
    }

    /**
     * Processes what we should do when the monster dies.
     *
     * - Handles rewarding the player
     *
     * @param int $characterId
     * @param int $monsterId
     * @param array $context
     * @param BattleRewardRequestSourceType $sourceType
     * @return void
     */
    public function processMonsterDeath(
        int $characterId,
        int $monsterId,
        array $context = [],
        BattleRewardRequestSourceType $sourceType = BattleRewardRequestSourceType::BATTLE,
    ): void {
        $character = Character::find($characterId);
        $monster = Monster::find($monsterId);

        if (! is_null($character) && ! is_null($monster)) {
            $this->weeklyBattleService->claimMonsterDeath($character, $monster);
        }

        $sourceId = $this->buildSourceId($sourceType, $characterId, $monsterId, $context);

        $enqueueResult = $this->battleRewardProcessingQueueManager->enqueue(
            $characterId,
            BattleRewardRequestPriority::SECOND,
            $sourceType,
            $sourceId,
            [
                'character_id' => $characterId,
                'monster_id' => $monsterId,
                'context' => $context,
            ],
        );

        if (! $enqueueResult->successful()) {
            $this->reportFailedRewardEnqueue($enqueueResult->failure(), $characterId, $monsterId, $sourceType, $sourceId);
        }
    }

    /**
     * Log and report a failed battle reward enqueue without granting a reward synchronously.
     *
     * @param ?Throwable $failure
     * @param int $characterId
     * @param int $monsterId
     * @param BattleRewardRequestSourceType $sourceType
     * @param string $sourceId
     * @return void
     */
    private function reportFailedRewardEnqueue(
        ?Throwable $failure,
        int $characterId,
        int $monsterId,
        BattleRewardRequestSourceType $sourceType,
        string $sourceId,
    ): void {
        $failureClass = is_null($failure) ? null : $failure::class;

        Log::channel('reward_processing')->error('Battle reward enqueue failed for a monster death.', [
            'character_id' => $characterId,
            'monster_id' => $monsterId,
            'source_type' => $sourceType->value,
            'source_id' => $sourceId,
            'exception_class' => $failureClass,
            'exception_message' => $failure?->getMessage(),
        ]);

        $this->monitoredBugReportService->reportError(
            'battle-reward-enqueue',
            $failure?->getMessage() ?? 'Battle reward enqueue failed without a recorded exception.',
            ['character_id' => $characterId, 'monster_id' => $monsterId, 'source_type' => $sourceType->value],
            $failureClass,
            $characterId,
            $sourceId,
        );
    }

    /**
     * Build the unique reward request source id for the Monster's death.
     *
     * @param BattleRewardRequestSourceType $sourceType
     * @param int $characterId
     * @param int $monsterId
     * @param array $context
     * @return string
     */
    private function buildSourceId(
        BattleRewardRequestSourceType $sourceType,
        int $characterId,
        int $monsterId,
        array $context,
    ): string {
        $uniqueTime = number_format(microtime(true), 6, '', '');

        if ($sourceType === BattleRewardRequestSourceType::EXPLORATION) {
            return implode(':', [
                $sourceType->value,
                $characterId,
                $context['exploration_log_id'],
                $monsterId,
                $uniqueTime,
            ]);
        }

        return implode(':', [
            $sourceType->value,
            $characterId,
            $monsterId,
            $uniqueTime,
        ]);
    }

    /**
     * Handle when a character revives.
     *
     * @param Character $character
     * @return Character
     */
    public function processRevive(Character $character): Character
    {
        $character->update([
            'is_dead' => false,
        ]);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();
        $characterHealth = $character->getInformation()->buildHealth();

        if (! is_null($characterInCelestialFight)) {
            $characterInCelestialFight->update([
                'character_current_health' => $characterHealth,
            ]);
        }

        $monsterFightCache = Cache::get('monster-fight-'.$character->id);

        if (! is_null($monsterFightCache)) {
            $monsterFightCache['health']['current_character_health'] = $characterHealth;

            Cache::put('monster-fight-'.$character->id, $monsterFightCache, 900);
        }

        event(new CharacterRevive($character->user, $characterHealth));

        event(new UpdateCharacterStatus($character));

        return $character->refresh();
    }
}
