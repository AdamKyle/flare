<?php

namespace Tests\Unit\Game\Monsters\Services;

use App\Flare\Values\MapNameValue;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Services\MonsterStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class MonsterStatsServiceTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateMonster, RefreshDatabase;

    private ?MonsterStatsService $service = null;

    private ?CharacterFactory $characterFactory = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(MonsterStatsService::class);
        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        $this->service = null;
        $this->characterFactory = null;

        parent::tearDown();
    }

    public function test_basic_map_returns_base_stat()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'str' => 10,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->service->getMonsterStats($character->refresh(), $monster);
        $row = $this->unwrap($response);

        $this->assertSame(10, $row['str']);
    }

    public function test_combined_map_and_location_percent_increase_scales_stats()
    {
        $hell = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $hell->id]);
        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $hell->id,
            'str' => 10,
        ]);

        $position = $character->map;

        $this->createLocation([
            'name' => 'Lava Ridge',
            'x' => $position->character_position_x,
            'y' => $position->character_position_y,
            'game_map_id' => $position->game_map_id,
            'enemy_strength_increase' => 2.0,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->service->getMonsterStats($character->refresh(), $monster);
        $row = $this->unwrap($response);

        $this->assertSame(31, $row['str']);
    }

    public function test_location_percent_increase_scales_stats_on_basic_map()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);
        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'str' => 10,
        ]);

        $position = $character->map;

        $this->createLocation([
            'name' => 'Ruins',
            'x' => $position->character_position_x,
            'y' => $position->character_position_y,
            'game_map_id' => $position->game_map_id,
            'enemy_strength_increase' => 2.0,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->service->getMonsterStats($character->refresh(), $monster);
        $row = $this->unwrap($response);

        $this->assertSame(30, $row['str']);
    }

    public function test_regular_monster_flag_is_returned()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_raid_monster' => false,
            'str' => 7,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->service->getMonsterStats($character->refresh(), $monster);
        $row = $this->unwrap($response);

        $this->assertFalse($row['is_raid_monster']);
    }

    public function test_raid_monster_returns_error_when_not_in_list()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);

        $raid = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_raid_monster' => true,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->service->getMonsterStats($character->refresh(), $raid);

        $this->assertArrayHasKey('message', $response);
    }

    public function test_special_map_with_purgatory_uses_regular_dataset()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
        ]);

        $ice = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => 1,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $ice->id]);

        $regular = $this->createMonster([
            'game_map_id' => $ice->id,
            'name' => 'Ice Regular',
        ]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'name' => 'Surface Easier',
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $character = $this->givePurgatoryAccess($character->refresh());

        $response = $this->service->getMonsterStats($character, $regular);
        $row = $this->unwrap($response);

        $this->assertSame('Ice Regular', $row['name']);
    }

    public function test_drop_chance_ignores_location_increase()
    {
        $hell = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $hell->id]);
        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $hell->id,
            'drop_check' => 0.80,
        ]);

        $position = $character->map;

        $this->createLocation([
            'name' => 'Lava Ridge',
            'x' => $position->character_position_x,
            'y' => $position->character_position_y,
            'game_map_id' => $position->game_map_id,
            'enemy_strength_increase' => 2.0,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->service->getMonsterStats($character->refresh(), $monster);
        $row = $this->unwrap($response);

        $this->assertEquals(0.80, $row['drop_chance']);
    }

    public function test_drop_chance_capped_at_ninety_nine()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $surface->id]);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'drop_check' => 0.995,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $response = $this->service->getMonsterStats($character->refresh(), $monster);
        $row = $this->unwrap($response);

        $this->assertEquals(0.99, $row['drop_chance']);
    }

    public function test_special_map_with_purgatory_uses_regular_dataset_drop_chance()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
        ]);

        $ice = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => 1,
        ]);

        $character = $this->characterFactory->getCharacter();
        $character->map()->update(['game_map_id' => $ice->id]);

        $regular = $this->createMonster([
            'game_map_id' => $ice->id,
            'drop_check' => 0.4,
        ]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'drop_check' => 0.2,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $character = $this->givePurgatoryAccess($character->refresh());

        $response = $this->service->getMonsterStats($character, $regular);
        $row = $this->unwrap($response);

        $this->assertEquals(0.4, $row['drop_chance']);
    }

    private function unwrap(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        $arr = array_key_exists('data', $response) && is_array($response['data'])
            ? $response['data']
            : $response;

        $numericOnly = array_filter($arr, function ($key) {
            return is_int($key);
        }, ARRAY_FILTER_USE_KEY);

        if (! empty($numericOnly)) {
            return array_values($numericOnly);
        }

        return $arr;
    }

    private function givePurgatoryAccess($character)
    {
        $slots = collect([['item' => ['effect' => \App\Flare\Values\ItemEffectsValue::PURGATORY]]]);

        $inventory = new class($slots)
        {
            public \Illuminate\Support\Collection $slots;

            public function __construct(\Illuminate\Support\Collection $slots)
            {
                $this->slots = $slots;
            }
        };

        $character->setRelation('inventory', $inventory);

        return $character;
    }
}
