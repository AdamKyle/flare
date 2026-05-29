<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Jobs\Exploration;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use Illuminate\Support\Facades\Cache;

class ExplorationAutomationService
{
    private int $timeDelay = 0;

    public function __construct(
        private readonly CharacterCacheData $characterCacheData,
        private readonly ExplorationCreatureCountCalculator $explorationCreatureCountCalculator
    ) {}

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

        $attackType = empty($params['attack_type']) ? AttackTypeValue::ATTACK : $params['attack_type'];

        $automation = CharacterAutomation::create([
            'character_id' => $character->id,
            'monster_id' => $selectedMonsterId,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHours($params['auto_attack_length'] ?? 1),
            'move_down_monster_list_every' => $params['move_down_the_list_every'] ?? null,
            'previous_level' => $character->level,
            'current_level' => $character->level,
            'attack_type' => $attackType,
            'started_in_special_location' => $this->startedInSpecialLocation($character),
        ]);

        $this->setTimeDelay();

        event(new UpdateCharacterStatus($character));

        $creatureCount = $this->explorationCreatureCountCalculator->calculate($character);

        event(new AutomationLogUpdate($character->user->id, 'The exploration will begin in 1 minute. Every 1 minute you will encounter ' . $creatureCount . ' enemies based on your fight timeout modifier.'));

        event(new AutomationTimeOut($character->user, now()->diffInSeconds($automation->completed_at)));

        $this->startAutomation($character, $automation->id, $attackType);
    }

    public function stopExploration(Character $character)
    {
        $characterAutomation = CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first();

        if (is_null($characterAutomation)) {
            return response()->json([
                'message' => 'Nope. You don\'t own that.',
            ], 422);
        }

        $characterAutomation->delete();

        $this->characterCacheData->deleteCharacterSheet($character);

        $character = $character->refresh();

        Cache::delete('can-character-survive-' . $character->id);

        event(new AutomationTimeOut($character->user, 0));
        event(new AutomationStatus($character->user, false));
        event(new UpdateCharacterStatus($character));
        event(new AutomationLogUpdate($character->user->id, 'Exploration has been stopped at player request.'));
    }

    public function getTimeDelay(): int
    {
        return $this->timeDelay;
    }

    public function setTimeDelay(): void
    {
        $this->timeDelay = 1;
    }

    protected function startAutomation(Character $character, int $automationId, string $attackType): void
    {
        Exploration::dispatch($character, $automationId, $attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');
    }

    private function startedInSpecialLocation(Character $character): bool
    {
        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (is_null($location)) {
            return false;
        }

        return ! is_null($location->type) || ! is_null($location->enemy_strength_type);
    }
}
