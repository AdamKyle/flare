<?php

namespace Tests\Unit\Game\Monsters\Services;

use App\Flare\Pagination\Pagination;
use App\Game\Monsters\Services\MonsterGemEffectContextService;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;

class MonsterGemEffectContextServiceTest extends TestCase
{
    use CreateGameMap, CreateMonster, RefreshDatabase;

    public function test_for_monster_exposes_to_hit_base_criticality_spell_damage_and_affix_damage_changes(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Detail Context Map']);
        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'damage_stat' => 'str',
            'str' => 10,
            'criticality' => 0.1,
            'max_spell_damage' => 50,
            'max_affix_damage' => 20,
        ]);

        Cache::put(MonsterCacheKey::MONSTERS->value, [
            $gameMap->name => [
                'data' => [[
                    'id' => $monster->id,
                    'gem_effect_context' => [
                        'has_effects' => true,
                        'context_type' => 'map',
                        'context_label' => $gameMap->name,
                        'game_map' => ['id' => $gameMap->id, 'name' => $gameMap->name],
                        'location' => null,
                        'sources' => [],
                        'character_power_reduction' => 0.0,
                    ],
                    'to_hit_base' => 15,
                    'criticality' => 0.2,
                    'spell_damage' => 75,
                    'max_affix_damage' => 30,
                ]],
            ],
        ]);

        $contexts = (new MonsterGemEffectContextService(resolve(Pagination::class)))->forMonster($monster);

        $changedFields = collect($contexts[0]['changed_values'])->pluck('field')->all();

        $this->assertContains('to_hit_base', $changedFields);
        $this->assertContains('criticality', $changedFields);
        $this->assertContains('spell_damage', $changedFields);
        $this->assertContains('max_affix_damage', $changedFields);
    }

    public function test_summary_returns_the_real_context_count_and_the_first_sorted_context_as_preview(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Summary Map']);
        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        Cache::put(MonsterCacheKey::MONSTERS->value, [
            $gameMap->name => [
                'data' => [[
                    'id' => $monster->id,
                    'gem_effect_context' => [
                        'has_effects' => true,
                        'context_type' => 'map',
                        'context_label' => $gameMap->name,
                        'game_map' => ['id' => $gameMap->id, 'name' => $gameMap->name],
                        'location' => null,
                        'sources' => [],
                        'character_power_reduction' => 0.0,
                    ],
                ]],
            ],
        ]);

        Cache::put(MonsterCacheKey::LOCATION_MONSTERS->value, [
            'Summary Location' => [
                'data' => [[
                    'id' => $monster->id,
                    'gem_effect_context' => [
                        'has_effects' => true,
                        'context_type' => 'location',
                        'context_label' => 'Summary Location',
                        'game_map' => null,
                        'location' => ['id' => 9001, 'name' => 'Summary Location'],
                        'sources' => [],
                        'character_power_reduction' => 0.0,
                    ],
                ]],
            ],
        ]);

        $summary = (new MonsterGemEffectContextService(resolve(Pagination::class)))->summary($monster);

        $this->assertSame(2, $summary['count']);
        $this->assertSame('map', $summary['preview']['type']);
    }

    public function test_paginate_returns_the_first_ten_contexts_with_can_load_more_true_and_the_remaining_context_on_page_two(): void
    {
        $monster = $this->createMonster();

        $rows = [];

        for ($locationId = 1; $locationId <= 11; $locationId++) {
            $rows[] = [
                'id' => $monster->id,
                'gem_effect_context' => [
                    'has_effects' => true,
                    'context_type' => 'location',
                    'context_label' => sprintf('Location %02d', $locationId),
                    'game_map' => null,
                    'location' => ['id' => $locationId, 'name' => sprintf('Location %02d', $locationId)],
                    'sources' => [],
                    'character_power_reduction' => 0.0,
                ],
            ];
        }

        Cache::put(MonsterCacheKey::MONSTERS->value, []);
        Cache::put(MonsterCacheKey::LOCATION_MONSTERS->value, [
            'Paginated Locations' => ['data' => $rows],
        ]);

        $service = new MonsterGemEffectContextService(resolve(Pagination::class));

        $firstPage = $service->paginate($monster, 10, 1);

        $this->assertCount(10, $firstPage['data']);
        $this->assertSame('Location 01', $firstPage['data'][0]['label']);
        $this->assertTrue($firstPage['meta']['can_load_more']);
        $this->assertSame(11, $firstPage['meta']['pagination']['total']);

        $secondPage = $service->paginate($monster, 10, 2);

        $this->assertCount(1, $secondPage['data']);
        $this->assertSame('Location 11', $secondPage['data'][0]['label']);
        $this->assertFalse($secondPage['meta']['can_load_more']);
    }

    public function test_raid_monster_produces_zero_context_count_and_null_preview_despite_cached_effects(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Excluded Map']);
        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'is_raid_monster' => true,
        ]);

        Cache::put(MonsterCacheKey::MONSTERS->value, [
            $gameMap->name => [
                'data' => [[
                    'id' => $monster->id,
                    'gem_effect_context' => [
                        'has_effects' => true,
                        'context_type' => 'map',
                        'context_label' => $gameMap->name,
                        'game_map' => ['id' => $gameMap->id, 'name' => $gameMap->name],
                        'location' => null,
                        'sources' => [],
                        'character_power_reduction' => 0.0,
                    ],
                ]],
            ],
        ]);

        $summary = (new MonsterGemEffectContextService(resolve(Pagination::class)))->summary($monster);

        $this->assertSame(0, $summary['count']);
        $this->assertNull($summary['preview']);
    }
}
