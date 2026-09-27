<?php

namespace App\Game\Automation\Delve\Services;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Maps\Values\LocationType;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class DelveStatusService
{
    /**
     * @param ItemTransformer $itemTransformer
     * @param DelveTelemetryService $delveTelemetryService
     */
    public function __construct(
        private readonly ItemTransformer $itemTransformer,
        private readonly DelveTelemetryService $delveTelemetryService,
    ) {}

    /**
     * Transform a Delve quest item into its API representation.
     *
     * @param Item $item
     * @return array
     */
    public function questItemDetail(Item $item): array
    {
        $fractalItem = new FractalItem($item, $this->itemTransformer);

        return (new Manager)->createData($fractalItem)->toArray()['data'];
    }

    /**
     * Return the character's current Delve status panel: active or completed.
     *
     * @param Character $character
     * @return array
     */
    public function statusForCharacter(Character $character): array
    {
        $activeDelve = DelveExploration::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->with('monster')
            ->first();

        if (! is_null($activeDelve)) {
            return $this->activeStatus($character, $activeDelve);
        }

        $completedDelve = DelveExploration::where('character_id', $character->id)
            ->whereNotNull('completed_at')
            ->whereNull('panel_dismissed_at')
            ->with('monster')
            ->latest('completed_at')
            ->first();

        if (is_null($completedDelve)) {
            return ['active' => false, 'completed' => false];
        }

        return $this->completedStatus($character, $completedDelve);
    }

    /**
     * Dismiss the character's completed Delve status panel.
     *
     * @param Character $character
     * @return void
     */
    public function dismissForCharacter(Character $character): void
    {
        DelveExploration::where('character_id', $character->id)
            ->whereNotNull('completed_at')
            ->whereNull('panel_dismissed_at')
            ->update(['panel_dismissed_at' => now()]);
    }

    /**
     * Build the status panel for an active Delve run.
     *
     * @param Character $character
     * @param DelveExploration $delve
     * @return array
     */
    private function activeStatus(Character $character, DelveExploration $delve): array
    {
        $latestLog = $delve->delveLogs()->latest()->first();
        $elapsedSeconds = $delve->started_at->diffInSeconds(now());
        $location = $this->caveLocation($character);
        $countdown = $this->questItemDropCountdown($delve, $location, $elapsedSeconds);
        $currentFoe = $this->currentFoe($delve, $latestLog);

        return array_merge([
            'active' => true,
            'completed' => false,
            'started_at' => $delve->started_at->toDateTimeString(),
            'elapsed_seconds' => $elapsedSeconds,
            'pack_size' => $delve->pack_size,
            'increase_enemy_strength' => $delve->increase_enemy_strength,
            'increase_percentage' => round(($delve->increase_enemy_strength ?? 0) * 100, 2),
            'quest_item_drop_hours_required' => $countdown['hours_required'],
            'quest_item_drop_seconds_remaining' => $countdown['seconds_remaining'],
            'quest_item_drop_available_at' => $countdown['available_at'],
            'quest_item_drop_available' => $countdown['available'],
            'quest_items' => is_null($location) ? [] : $this->questItems($character, $location),
            'reward_checkpoints' => $this->rewardCheckpoints($elapsedSeconds / 3600),
            'monster_name' => $delve->monster?->name,
            'enemy_stats_available' => $currentFoe['stats_available'],
            'current_foe' => $currentFoe,
        ], $this->delveTelemetryService->telemetry($delve));
    }

    /**
     * Build the status panel for a completed Delve run.
     *
     * @param Character $character
     * @param DelveExploration $delve
     * @return array
     */
    private function completedStatus(Character $character, DelveExploration $delve): array
    {
        $latestLog = $delve->delveLogs()->latest()->first();
        $elapsedSeconds = $delve->started_at->diffInSeconds($delve->completed_at);
        $location = $this->caveLocation($character);
        $currentFoe = $this->currentFoe($delve, $latestLog);
        $reason = $delve->ended_reason ?? $latestLog?->outcome ?? 'completed';

        return array_merge([
            'active' => false,
            'completed' => true,
            'id' => $delve->id,
            'started_at' => $delve->started_at->toDateTimeString(),
            'completed_at' => $delve->completed_at->toDateTimeString(),
            'elapsed_seconds' => $elapsedSeconds,
            'pack_size' => $delve->pack_size,
            'increase_enemy_strength' => $delve->increase_enemy_strength,
            'increase_percentage' => round(($delve->increase_enemy_strength ?? 0) * 100, 2),
            'reason' => $reason,
            'message' => 'Delve ended.',
            'quest_items' => is_null($location) ? [] : $this->questItems($character, $location),
            'reward_checkpoints' => $this->rewardCheckpoints($elapsedSeconds / 3600),
            'monster_name' => $delve->monster?->name,
            'enemy_stats_available' => $currentFoe['stats_available'],
            'current_foe' => $currentFoe,
        ], $this->delveTelemetryService->telemetry($delve));
    }

    /**
     * Resolve the current foe's display stats from the latest Delve log or the Delve's randomly selected monster.
     *
     * @param DelveExploration $delve
     * @param ?DelveLog $latestLog
     * @return array
     */
    private function currentFoe(DelveExploration $delve, ?DelveLog $latestLog): array
    {
        $fightMonster = $latestLog?->fight_data['monster'] ?? [];

        if (! empty($fightMonster)) {
            return $this->latestLogFoe($latestLog, $fightMonster);
        }

        if (! is_null($delve->monster)) {
            return [
                'id' => $delve->monster->id,
                'name' => $delve->monster->name,
                'pack_size' => $delve->pack_size,
                'enemy_strength_boost' => $delve->increase_enemy_strength ?? 0,
                'stats_available' => true,
                'stats' => $this->normalizeMonsterModelStats($delve->monster),
                'source' => 'active_delve',
                'message' => "Showing the randomly selected Delve monster's base stats. Combat-adjusted stats update after each Delve round.",
            ];
        }

        return [
            'id' => null,
            'name' => null,
            'pack_size' => $delve->pack_size,
            'enemy_strength_boost' => 0,
            'stats_available' => false,
            'stats' => [],
            'source' => 'waiting',
            'message' => 'Waiting for Delve encounter',
        ];
    }

    /**
     * Build the current foe's display stats from the monster recorded on the latest Delve round log.
     *
     * @param DelveLog $latestLog
     * @param array $fightMonster
     * @return array
     */
    private function latestLogFoe(DelveLog $latestLog, array $fightMonster): array
    {
        $name = $fightMonster['name'] ?? null;
        $packSize = $latestLog->pack_size;
        $packPrefix = $packSize > 1 ? 'You are fighting '.$packSize.' of '.$name.'. ' : '';
        $statDescription = 'Showing stats from the most recent Delve round. These stats may reflect a previous battle state and update every time a new round begins down here in the delve.';

        return [
            'id' => $fightMonster['id'] ?? null,
            'name' => $name,
            'pack_size' => $packSize,
            'enemy_strength_boost' => $latestLog->increased_enemy_strength ?? 0,
            'stats_available' => true,
            'stats' => [
                'str' => $fightMonster['str'] ?? 0,
                'dur' => $fightMonster['dur'] ?? 0,
                'dex' => $fightMonster['dex'] ?? 0,
                'chr' => $fightMonster['chr'] ?? 0,
                'int' => $fightMonster['int'] ?? 0,
                'agi' => $fightMonster['agi'] ?? 0,
                'focus' => $fightMonster['focus'] ?? 0,
                'ac' => $fightMonster['ac'] ?? 0,
                'health_range' => $fightMonster['health_range'] ?? null,
                'attack_range' => $fightMonster['attack_range'] ?? null,
                'max_spell_damage' => $fightMonster['spell_damage'] ?? null,
                'healing_percentage' => $fightMonster['max_healing'] ?? null,
                'max_level' => $fightMonster['max_level'] ?? null,
            ],
            'source' => 'latest_log',
            'message' => $packPrefix.$statDescription,
        ];
    }

    /**
     * Normalize a monster model's stats into the Delve current-foe stats shape.
     *
     * @param Monster $monster
     * @return array
     */
    private function normalizeMonsterModelStats(Monster $monster): array
    {
        $statFields = [
            'str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus', 'ac',
            'health_range', 'attack_range', 'max_spell_damage', 'healing_percentage',
            'xp', 'max_level', 'gold',
        ];

        return collect($statFields)
            ->mapWithKeys(fn (string $field): array => [$field => $monster->{$field}])
            ->reject(fn ($value): bool => is_null($value))
            ->all();
    }

    /**
     * Resolve the character's current Cave of Memories location, if standing in one.
     *
     * @param Character $character
     * @return ?Location
     */
    private function caveLocation(Character $character): ?Location
    {
        return Location::where('type', LocationType::CAVE_OF_SHADOWS->value)
            ->where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->whereNotNull('hours_to_drop')
            ->first();
    }

    /**
     * Calculate the countdown until the Delve location's quest item becomes available.
     *
     * @param DelveExploration $delve
     * @param ?Location $location
     * @param int $elapsedSeconds
     * @return array
     */
    private function questItemDropCountdown(DelveExploration $delve, ?Location $location, int $elapsedSeconds): array
    {
        if (is_null($location)) {
            return [
                'hours_required' => null,
                'seconds_remaining' => null,
                'available_at' => null,
                'available' => false,
            ];
        }

        $hoursRequired = $location->hours_to_drop;

        if (is_null($hoursRequired) || $hoursRequired <= 0) {
            return [
                'hours_required' => $hoursRequired,
                'seconds_remaining' => 0,
                'available_at' => $delve->started_at->toDateTimeString(),
                'available' => true,
            ];
        }

        $secondsRequired = $hoursRequired * 3600;
        $secondsRemaining = max(0, $secondsRequired - $elapsedSeconds);

        return [
            'hours_required' => $hoursRequired,
            'seconds_remaining' => $secondsRemaining,
            'available_at' => $delve->started_at->addSeconds($secondsRequired)->toDateTimeString(),
            'available' => $secondsRemaining === 0,
        ];
    }

    /**
     * Build the character's Delve quest item availability list for the location.
     *
     * @param Character $character
     * @param Location $location
     * @return array
     */
    private function questItems(Character $character, Location $location): array
    {
        $items = Item::where('drop_location_id', $location->id)
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->where('type', 'quest')
            ->get();

        if ($items->isEmpty()) {
            return [];
        }

        $inventoryId = Inventory::where('character_id', $character->id)->value('id');

        $ownedSlots = InventorySlot::where('inventory_id', $inventoryId)
            ->whereIn('item_id', $items->pluck('id'))
            ->select(['item_id', 'id'])
            ->get()
            ->keyBy('item_id');

        $hadItemIds = $this->previouslyHeldQuestItemIds($character);

        return $items->map(function (Item $item) use ($ownedSlots, $hadItemIds): array {
            $slot = $ownedSlots->get($item->id);

            return [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'drop_chance' => null,
                'monster_name' => null,
                'slot_id' => $slot?->id,
                'have' => ! is_null($slot),
                'had' => in_array($item->id, $hadItemIds, true),
            ];
        })->values()->all();
    }

    /**
     * Resolve the quest item ids the character has handed in for completed quests.
     *
     * @param Character $character
     * @return array
     */
    private function previouslyHeldQuestItemIds(Character $character): array
    {
        $completedQuestIds = $character->questsCompleted()
            ->whereNotNull('quest_id')
            ->pluck('quest_id')
            ->all();

        if (empty($completedQuestIds)) {
            return [];
        }

        return Quest::query()
            ->whereIn('id', $completedQuestIds)
            ->get(['item_id', 'secondary_required_item'])
            ->flatMap(function (Quest $quest): array {
                return [$quest->item_id, $quest->secondary_required_item];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build the Delve duration-based reward checkpoint list with their reached state.
     *
     * @param float $elapsedHours
     * @return array
     */
    private function rewardCheckpoints(float $elapsedHours): array
    {
        return [
            [
                'label' => 'Base reward',
                'requirement' => 'Any duration',
                'gold' => '1,000',
                'special_item' => null,
                'reached' => true,
            ],
            [
                'label' => '2+ hour reward',
                'requirement' => '>= 2 hours',
                'gold' => '1,000,000',
                'special_item' => 'Unique',
                'reached' => $elapsedHours >= 2,
            ],
            [
                'label' => '4+ hour reward',
                'requirement' => '>= 4 hours',
                'gold' => '1,000,000,000',
                'special_item' => 'Mythic',
                'reached' => $elapsedHours >= 4,
            ],
            [
                'label' => '6+ hour reward',
                'requirement' => '>= 6 hours',
                'gold' => '1,000,000,000,000',
                'special_item' => 'Cosmic',
                'reached' => $elapsedHours >= 6,
            ],
        ];
    }
}
