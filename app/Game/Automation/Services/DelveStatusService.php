<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Values\LocationType;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class DelveStatusService
{
    public function questItemDetail(Item $item): array
    {
        $fractalItem = new FractalItem($item, new ItemTransformer);

        return (new Manager)->createData($fractalItem)->toArray()['data'];
    }

    public function statusForCharacter(Character $character): array
    {
        $delve = DelveExploration::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->with('monster')
            ->first();

        if (is_null($delve)) {
            return ['active' => false];
        }

        $latestLog = $delve->delveLogs()->latest()->first();
        $elapsedSeconds = now()->diffInSeconds($delve->started_at);
        $elapsedHours = $elapsedSeconds / 3600;
        $location = $this->caveLocation($character);
        $countdown = $this->questItemDropCountdown($delve, $location, $elapsedSeconds);
        $currentFoe = $this->currentFoe($delve, $latestLog);

        return [
            'active' => true,
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

    private function currentFoe(DelveExploration $delve, ?DelveLog $latestLog): array
    {
        if (!is_null($latestLog)) {
            $fightMonster = [];

            if (is_array($latestLog->fight_data) && !empty($latestLog->fight_data['monster'])) {
                $fightMonster = $latestLog->fight_data['monster'];
            }

            if (!empty($fightMonster)) {
                $name = $fightMonster['name'] ?? null;
                $packSize = $latestLog->pack_size;
                $packPrefix = $packSize > 1 ? 'You are fighting ' . $packSize . ' of ' . $name . '. ' : '';
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
                    'message' => $packPrefix . $statDescription,
                ];
            }
        }

        if (!is_null($delve->monster)) {
            $monster = $delve->monster;
            $normalizedStats = $this->normalizeMonsterModelStats($monster);

            if (!empty($normalizedStats)) {
                return [
                    'id' => $monster->id,
                    'name' => $monster->name,
                    'pack_size' => 1,
                    'enemy_strength_boost' => $delve->increase_enemy_strength ?? 0,
                    'stats_available' => true,
                    'stats' => $normalizedStats,
                    'source' => 'active_delve',
                    'message' => 'Showing selected monster base stats. Combat-adjusted stats update after each Delve round.',
                ];
            }

            return [
                'id' => $monster->id,
                'name' => $monster->name,
                'pack_size' => 1,
                'enemy_strength_boost' => $delve->increase_enemy_strength ?? 0,
                'stats_available' => false,
                'stats' => [],
                'source' => 'active_delve',
                'message' => 'Selected monster found, but base stats are not available from existing data.',
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

            if (!is_null($value)) {
                $stats[$field] = $value;
            }
        }

        return $stats;
    }

    private function caveLocation(Character $character): ?Location
    {
        return Location::where('type', LocationType::CAVE_OF_MEMORIES)
            ->where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->whereNotNull('hours_to_drop')
            ->first();
    }

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
