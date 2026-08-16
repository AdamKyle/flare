<?php

namespace App\Game\Automation\Exploration\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Exploration\Jobs\Exploration;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Combat\Values\AttackType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ExplorationAutomationService
{
    private int $timeDelay = 0;

    /**
     * @param  CharacterCacheData  $characterCacheData  The character cache data service.
     * @param  ExplorationCreatureCountCalculator  $explorationCreatureCountCalculator  The creature count calculator.
     * @param  ExplorationLogService  $explorationLogService  The Exploration log service.
     * @param  ExplorationWarningService  $explorationWarningService  The Exploration warning service.
     */
    public function __construct(
        private readonly CharacterCacheData $characterCacheData,
        private readonly ExplorationCreatureCountCalculator $explorationCreatureCountCalculator,
        private readonly ExplorationLogService $explorationLogService,
        private readonly ExplorationWarningService $explorationWarningService,
    ) {}

    /**
     * Start Exploration automation for the character with the given options.
     *
     * @param  Character  $character  The character starting Exploration.
     * @param  array  $params  The Exploration start options.
     * @return void
     */
    public function beginAutomation(Character $character, array $params)
    {
        $selectedMonsterId = $params['selected_monster_id'] ?? null;

        if (empty($selectedMonsterId)) {
            $selectedMonsterId = Monster::where('is_celestial_entity', false)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('game_map_id', $character->map->game_map_id)
                ->whereNull('only_for_location_type')
                ->whereNull('raid_special_attack_type')
                ->orderBy('max_level')
                ->first()?->id;
        }

        $attackType = empty($params['attack_type']) ? AttackType::ATTACK->value : $params['attack_type'];

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

        $this->explorationWarningService->dismiss($character);
        $this->explorationLogService->start($character, $automation);

        $this->setTimeDelay();

        event(new UpdateCharacterStatus($character));

        $creatureCount = $this->explorationCreatureCountCalculator->calculate($character);

        event(new AutomationLogUpdate($character->user->id, 'The exploration will begin in 1 minute. Every 1 minute you will encounter '.$creatureCount.' enemies based on your fight timeout modifier.'));

        event(new AutomationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        $this->startAutomation($character, $automation->id, $attackType);
    }

    /**
     * Stop the character's active Exploration automation.
     *
     * @param  Character  $character  The character stopping Exploration.
     * @return JsonResponse|void
     */
    public function stopExploration(Character $character)
    {
        $characterAutomation = CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING->value)->first();

        if (is_null($characterAutomation)) {
            return response()->json([
                'message' => 'Nope. You don\'t own that.',
            ], 422);
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
    }

    /**
     * Get the configured start delay, in minutes, before Exploration begins.
     *
     * @return int The configured start delay in minutes.
     */
    public function getTimeDelay(): int
    {
        return $this->timeDelay;
    }

    /**
     * Set the start delay, in minutes, before Exploration begins.
     *
     * @return void This method does not return a value.
     */
    public function setTimeDelay(): void
    {
        $this->timeDelay = 1;
    }

    /**
     * Dispatch the delayed Exploration job for the character's automation.
     *
     * @param  Character  $character  The character exploring.
     * @param  int  $automationId  The character automation id.
     * @param  string  $attackType  The selected attack type.
     * @return void This method does not return a value.
     */
    protected function startAutomation(Character $character, int $automationId, string $attackType): void
    {
        Exploration::dispatch($character, $automationId, $attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onConnection('long_running')->onQueue('exploration');
    }

    /**
     * Determine whether the character began Exploration while standing in a special location.
     *
     * @param  Character  $character  The character starting Exploration.
     * @return bool True when the character is standing in a special location.
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
