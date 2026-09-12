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
     * @param ItemTransformer $itemTransformer The item transformer.
     */
    public function __construct(private readonly ItemTransformer $itemTransformer) {}

    /**
     * Transform a Delve quest item into its API representation.
     *
     * @param Item $item The quest item to transform.
     * @return array The transformed item data.
     */
    public function questItemDetail(Item $item): array
    {
        $fractalItem = new FractalItem($item, $this->itemTransformer);

        return (new Manager)->createData($fractalItem)->toArray()['data'];
    }

    /**
     * Return the character's current Delve status panel: active or completed.
     *
     * @param Character $character The character to resolve status for.
     * @return array The current Delve status panel.
     */
    public function statusForCharacter(Character $character): array
    {
        $delve = DelveExploration::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->with('monster')
            ->first();

        if (is_null($delve)) {
            $delve = DelveExploration::where('character_id', $character->id)
                ->whereNotNull('completed_at')
                ->whereNull('panel_dismissed_at')
                ->with('monster')
                ->latest('completed_at')
                ->first();

            if (is_null($delve)) {
                return ['active' => false, 'completed' => false];
            }

            return $this->completedStatus($character, $delve);
        }

        $latestLog = $delve->delveLogs()->latest()->first();
        $elapsedSeconds = $delve->started_at->diffInSeconds(now());
        $elapsedHours = $elapsedSeconds / 3600;
        $location = $this->caveLocation($character);
        $countdown = $this->questItemDropCountdown($delve, $location, $elapsedSeconds);
        $currentFoe = $this->currentFoe($delve, $latestLog);

        return [
            'active' => true,
            'completed' => false,
            'started_at' => $delve->started_at->toDateTimeString(),
            'elapsed_seconds' => $elapsedSeconds,
            'increase_enemy_strength' => $delve->increase_enemy_strength,
            'increase_percentage' => round(($delve->increase_enemy_strength ?? 0) * 100, 2),
            'quest_item_drop_hours_required' => $countdown['hours_required'],
            'quest_item_drop_seconds_remaining' => $countdown['seconds_remaining'],
            'quest_item_drop_available_at' => $countdown['available_at'],
            'quest_item_drop_available' => $countdown['available'],
            'quest_items' => is_null($location) ? [] : $this->questItems($character, $location),
            'reward_checkpoints' => $this->rewardCheckpoints($elapsedHours),
            'monster_name' => $delve->monster?->name,
            'enemy_stats_available' => $currentFoe['stats_available'],
            'current_foe' => $currentFoe,
        ];
    }

    /**
     * Dismiss the character's completed Delve status panel.
     *
     * @param Character $character The character dismissing the panel.
     * @return void This method does not return a value.
     */
    public function dismissForCharacter(Character $character): void
    {
        DelveExploration::where('character_id', $character->id)
            ->whereNotNull('completed_at')
            ->whereNull('panel_dismissed_at')
            ->update(['panel_dismissed_at' => now()]);
    }

    /**
     * Build the status panel for a completed Delve run.
     *
     * @param Character $character The character who completed the Delve run.
     * @param DelveExploration $delve The completed Delve exploration record.
     * @return array The completed Delve status panel.
     */
    private function completedStatus(Character $character, DelveExploration $delve): array
    {
        $latestLog = $delve->delveLogs()->latest()->first();
        $elapsedSeconds = $delve->started_at->diffInSeconds($delve->completed_at);
        $elapsedHours = $elapsedSeconds / 3600;
        $location = $this->caveLocation($character);
        $currentFoe = $this->currentFoe($delve, $latestLog);
        $reason = $delve->ended_reason ?? $latestLog?->outcome ?? 'completed';

        return [
            'active' => false,
            'completed' => true,
            'id' => $delve->id,
            'started_at' => $delve->started_at->toDateTimeString(),
            'completed_at' => $delve->completed_at->toDateTimeString(),
            'elapsed_seconds' => $elapsedSeconds,
            'increase_enemy_strength' => $delve->increase_enemy_strength,
            'increase_percentage' => round(($delve->increase_enemy_strength ?? 0) * 100, 2),
            'reason' => $reason,
            'message' => 'Delve ended.',
            'quest_items' => is_null($location) ? [] : $this->questItems($character, $location),
            'reward_checkpoints' => $this->rewardCheckpoints($elapsedHours),
            'monster_name' => $delve->monster?->name,
            'enemy_stats_available' => $currentFoe['stats_available'],
            'current_foe' => $currentFoe,
        ];
    }

    /**
     * Resolve the current foe's display stats from the latest Delve log or the active delve's monster.
     *
     * @param DelveExploration $delve The active or completed Delve record.
     * @param DelveLog|null $latestLog The most recent Delve round log, if any.
     * @return array The current foe's display stats.
     */
    private function currentFoe(DelveExploration $delve, ?DelveLog $latestLog): array
    {
        if (! is_null($latestLog)) {
            $fightMonster = [];

            if (is_array($latestLog->fight_data) && ! empty($latestLog->fight_data['monster'])) {
                $fightMonster = $latestLog->fight_data['monster'];
            }

            if (! empty($fightMonster)) {
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
        }

        if (! is_null($delve->monster)) {
            $monster = $delve->monster;

            return [
                'id' => $monster->id,
                'name' => $monster->name,
                'pack_size' => 1,
                'enemy_strength_boost' => $delve->increase_enemy_strength ?? 0,
                'stats_available' => true,
                'stats' => $this->normalizeMonsterModelStats($monster),
                'source' => 'active_delve',
                'message' => 'Showing selected monster base stats. Combat-adjusted stats update after each Delve round.',
            ];
        }

        return [
            'id' => null,
            'name' => null,
            'pack_size' => 1,
            'enemy_strength_boost' => 0,
            'stats_available' => false,
            'stats' => [],
            'source' => 'waiting',
            'message' => 'Waiting for Delve encounter',
        ];
    }

    /**
     * Normalize a monster model's stats into the Delve current-foe stats shape.
     *
     * @param Monster $monster The monster model to normalize.
     * @return array The normalized monster stats.
     */
    private function normalizeMonsterModelStats(Monster $monster): array
    {
        $stats = [];
        $statFields = [
            'str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus', 'ac',
            'health_range', 'attack_range', 'max_spell_damage', 'healing_percentage',
            'xp', 'max_level', 'gold',
        ];

        foreach ($statFields as $field) {
            $value = $monster->{$field};

            if (! is_null($value)) {
                $stats[$field] = $value;
            }
        }

        return $stats;
    }

    /**
     * Resolve the character's current Cave of Memories location, if standing in one.
     *
     * @param Character $character The character to resolve the location for.
     * @return Location|null The character's current Cave of Memories location, if any.
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
     * @param DelveExploration $delve The active Delve record.
     * @param Location|null $location The character's current Delve location, if any.
     * @param int $elapsedSeconds The number of seconds elapsed in the Delve run.
     * @return array The quest item drop countdown data.
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
     * @param Character $character The character to resolve availability for.
     * @param Location $location The Delve location.
     * @return array The quest item availability list.
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

        $ownedSlots = $inventoryId
            ? InventorySlot::where('inventory_id', $inventoryId)
                ->whereIn('item_id', $items->pluck('id'))
                ->select(['item_id', 'id'])
                ->get()
                ->keyBy('item_id')
            : collect();

        $completedQuestIds = $character->questsCompleted()
            ->whereNotNull('quest_id')
            ->pluck('quest_id')
            ->all();

        $hadItemIds = [];

        if (! empty($completedQuestIds)) {
            $hadItemIds = Quest::query()
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

        $result = [];

        foreach ($items as $item) {
            $slot = $ownedSlots->get($item->id);

            $result[] = [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'drop_chance' => null,
                'monster_name' => null,
                'slot_id' => $slot?->id,
                'have' => ! is_null($slot),
                'had' => in_array($item->id, $hadItemIds, true),
            ];
        }

        return $result;
    }

    /**
     * Build the Delve duration-based reward checkpoint list with their reached state.
     *
     * @param float $elapsedHours The number of hours elapsed in the Delve run.
     * @return array The reward checkpoint list.
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
