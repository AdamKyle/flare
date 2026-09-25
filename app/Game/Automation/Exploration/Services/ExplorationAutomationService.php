<?php

namespace App\Game\Automation\Exploration\Services;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Exploration\Jobs\Exploration;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExplorationAutomationService
{
    use ResponseBuilder;

    private int $timeDelay = 0;

    /**
     * @param CharacterCacheData $characterCacheData
     * @param ExplorationCreatureCountCalculator $explorationCreatureCountCalculator
     * @param ExplorationLogService $explorationLogService
     * @param ExplorationWarningService $explorationWarningService
     * @param AutomationRestrictionService $automationRestrictionService
     * @param MonitoredBugReportService $monitoredBugReportService
     */
    public function __construct(
        private readonly CharacterCacheData $characterCacheData,
        private readonly ExplorationCreatureCountCalculator $explorationCreatureCountCalculator,
        private readonly ExplorationLogService $explorationLogService,
        private readonly ExplorationWarningService $explorationWarningService,
        private readonly AutomationRestrictionService $automationRestrictionService,
        private readonly MonitoredBugReportService $monitoredBugReportService,
    ) {}

    /**
     * Start Exploration automation for the character with the given options.
     *
     * @param Character $character
     * @param array $params
     * @return array
     */
    public function beginAutomation(Character $character, array $params): array
    {
        $selectedMonsterId = $params['selected_monster_id'];
        $attackType = empty($params['attack_type']) ? AttackType::ATTACK->value : $params['attack_type'];

        $automation = $this->createAutomationWithLog($character, $selectedMonsterId, $attackType, $params);

        if (is_null($automation)) {
            return $this->errorResult('Unable to start Exploration right now. Please try again.');
        }

        $this->explorationWarningService->dismiss($character);
        $this->explorationLogService->outputForCharacter($character);
        $this->setTimeDelay();

        event(new UpdateCharacterStatus($character));

        $creatureCount = $this->explorationCreatureCountCalculator->calculate($character);

        $startMessage = new AutomationLogUpdate($character->user->id, 'The exploration will begin in 1 minute. Every 1 minute you will encounter '.$creatureCount.' enemies based on your fight timeout modifier.');

        event($startMessage);

        event(new AutomationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        $this->startAutomation($character, $automation->id, $attackType);

        return $this->successResult([
            'exploration_message' => $this->formatStartMessage($startMessage),
        ]);
    }

    /**
     * Build the start-message payload returned to the client from the dispatched Exploration log event.
     *
     * @param AutomationLogUpdate $startMessage
     * @return array
     */
    private function formatStartMessage(AutomationLogUpdate $startMessage): array
    {
        return [
            'messageId' => $startMessage->messageId,
            'message' => $startMessage->message,
            'makeItalic' => $startMessage->makeItalic,
            'isReward' => $startMessage->isReward,
            'timeStamp' => $startMessage->timeStamp,
        ];
    }

    /**
     * Stop the character's active Exploration automation.
     *
     * @param Character $character
     * @return array
     */
    public function stopExploration(Character $character): array
    {
        $characterAutomation = $this->automationRestrictionService->activeAutomationOfType($character, AutomationType::EXPLORING->value);

        if (is_null($characterAutomation)) {
            return $this->errorResult('Nope. You don\'t own that.');
        }

        $activeLog = $this->explorationLogService->activeForCharacter($character);

        $characterAutomation->delete();

        $this->characterCacheData->deleteCharacterSheet($character);

        $character = $character->refresh();

        Cache::delete('can-character-survive-'.$character->id);

        if (! is_null($activeLog)) {
            $this->explorationLogService->finalize($activeLog, 'player_stopped', true);
        }

        $this->explorationWarningService->getState($character);

        event(new AutomationTimeOut($character->user, 0));
        event(new AutomationStatus($character->user, false));
        event(new UpdateCharacterStatus($character));
        event(new AutomationLogUpdate($character->user->id, 'Exploration has been stopped at player request.'));

        return $this->successResult();
    }

    /**
     * Get the configured start delay, in minutes, before Exploration begins.
     *
     * @return int
     */
    public function getTimeDelay(): int
    {
        return $this->timeDelay;
    }

    /**
     * Set the start delay, in minutes, before Exploration begins.
     *
     * @return void
     */
    public function setTimeDelay(): void
    {
        $this->timeDelay = 1;
    }

    /**
     * Atomically create the new Exploration automation and its required initial Exploration log.
     *
     * @param Character $character
     * @param int $selectedMonsterId
     * @param string $attackType
     * @param array $params
     * @return CharacterAutomation|null
     */
    private function createAutomationWithLog(Character $character, int $selectedMonsterId, string $attackType, array $params): ?CharacterAutomation
    {
        try {
            return DB::transaction(function () use ($character, $selectedMonsterId, $attackType, $params): CharacterAutomation {
                $automation = CharacterAutomation::create([
                    'character_id' => $character->id,
                    'monster_id' => $selectedMonsterId,
                    'type' => AutomationType::EXPLORING->value,
                    'started_at' => now(),
                    'completed_at' => now()->addHours($params['auto_attack_length'] ?? 1),
                    'move_down_monster_list_every' => $params['move_down_the_list_every'] ?? null,
                    'previous_level' => $character->level,
                    'current_level' => $character->level,
                    'attack_type' => $attackType,
                    'started_in_special_location' => $this->startedInSpecialLocation($character),
                ]);

                $this->explorationLogService->start($character, $automation);

                return $automation;
            });
        } catch (Throwable $throwable) {
            $this->reportStartFailure($character, $selectedMonsterId, $throwable);

            return null;
        }
    }

    /**
     * Log and report an unexpected Exploration start failure.
     *
     * @param Character $character
     * @param int $selectedMonsterId
     * @param Throwable $throwable
     * @return void
     */
    private function reportStartFailure(Character $character, int $selectedMonsterId, Throwable $throwable): void
    {
        $context = [
            'character_id' => $character->id,
            'selected_monster_id' => $selectedMonsterId,
            'exception' => $throwable,
        ];

        Log::error('Exploration automation failed to start.', $context);

        $this->monitoredBugReportService->reportError(
            'exploration',
            $throwable->getMessage(),
            ['selected_monster_id' => $selectedMonsterId],
            $throwable::class,
            $character->id,
        );
    }

    /**
     * Dispatch the delayed Exploration job for the character's automation.
     *
     * @param Character $character
     * @param int $automationId
     * @param string $attackType
     * @return void
     */
    private function startAutomation(Character $character, int $automationId, string $attackType): void
    {
        Exploration::dispatch($character, $automationId, $attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onConnection('long_running')->onQueue('exploration');
    }

    /**
     * Determine whether the character began Exploration while standing in a special location.
     *
     * @param Character $character
     * @return bool
     */
    private function startedInSpecialLocation(Character $character): bool
    {
        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (is_null($location)) {
            return false;
        }

        return ! is_null($location->type);
    }
}
