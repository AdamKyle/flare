<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Flare\Models\Location;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Services\MonsterListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class MonsterListServiceTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    private ?MonsterListService $service;

    private ?CharacterFactory $characterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(MonsterListService::class);

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;

        $this->characterFactory = null;

        Mockery::close();
    }

    public function test_builds_cache_when_missing_and_uses_map_monsters()
    {
        Cache::forget('monsters');

        $character = $this->characterFactory->getCharacter();

        $mapName = $character->map->gameMap->name;

        $mock = Mockery::mock(BuildMonsterCacheService::class);

        $mock->shouldReceive('buildCache')->once()->andReturnUsing(function () use ($mapName) {
            Cache::put('monsters', [
                $mapName => [
                    'data' => [
                        ['id' => 1, 'name' => 'Map Base A', 'max_level' => 10],
                        ['id' => 2, 'name' => 'Map Base B', 'max_level' => 12],
                    ],
                    'regular' => [
                        'data' => [
                            ['id' => 21, 'name' => 'Map Regular', 'max_level' => 15],
                        ],
                    ],
                    'easier' => [
                        'data' => [
                            ['id' => 11, 'name' => 'Map Easier', 'max_level' => 8],
                        ],
                    ],
                ],
            ], 60);
        });

        $this->instance(BuildMonsterCacheService::class, $mock);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 1, 'name' => 'Map Base A', 'max_level' => 10],
            ['id' => 2, 'name' => 'Map Base B', 'max_level' => 12],
        ], $data);
    }

    public function test_location_with_effect_on_non_ice_plane_overrides_with_location_monsters()
    {
        $character = $this->characterFactory->getCharacter();

        $mapName = $character->map->gameMap->name;

        $this->seedMonstersCache([
            $mapName => [
                'data' => [
                    ['id' => 1, 'name' => 'Map Base', 'max_level' => 10],
                ],
                'regular' => ['data' => [['id' => 21, 'name' => 'Map Regular', 'max_level' => 15]]],
                'easier' => ['data' => [['id' => 11, 'name' => 'Map Easier', 'max_level' => 8]]],
            ],
            'Volcano' => [
                'data' => [
                    ['id' => 31, 'name' => 'Volcano Fiend', 'max_level' => 20],
                ],
            ],
        ]);

        $this->seedSpecialLocationCache([]);

        $pos = $character->map;

        Location::factory()->create([
            'name' => 'Volcano',
            'x' => $pos->character_position_x,
            'y' => $pos->character_position_y,
            'game_map_id' => $pos->game_map_id,
            'enemy_strength_increase' => 0.10,
        ]);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 31, 'name' => 'Volcano Fiend', 'max_level' => 20],
        ], $data);
    }

    public function test_location_with_effect_on_ice_plane_without_purgatory_uses_easier()
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap(['name' => MapNameValue::ICE_PLANE])->id,
        ]);

        $character = $character->refresh();

        $mapName = $character->map->gameMap->name;

        $this->seedMonstersCache([
            $mapName => [
                'data' => [['id' => 1, 'name' => 'Ice Base', 'max_level' => 10]],
                'regular' => ['data' => [['id' => 21, 'name' => 'Ice Regular', 'max_level' => 15]]],
                'easier' => ['data' => [['id' => 11, 'name' => 'Ice Easier', 'max_level' => 8]]],
            ],
            'Frost Rift' => [
                'data' => [['id' => 31, 'name' => 'Frost Rift Brute', 'max_level' => 20]],
            ],
        ]);

        $this->seedSpecialLocationCache([]);

        $pos = $character->map;

        Location::factory()->create([
            'name' => 'Frost Rift',
            'x' => $pos->character_position_x,
            'y' => $pos->character_position_y,
            'game_map_id' => $pos->game_map_id,
            'enemy_strength_increase' => 0.20,
        ]);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 11, 'name' => 'Ice Easier', 'max_level' => 8],
        ], $data);
    }

    public function test_location_with_effect_on_ice_plane_with_purgatory_uses_regular()
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap(['name' => MapNameValue::ICE_PLANE])->id,
        ]);

        $character = $this->givePurgatoryAccess($character);

        $mapName = $character->map->gameMap->name;

        $this->seedMonstersCache([
            $mapName => [
                'data' => [['id' => 1, 'name' => 'Ice Base', 'max_level' => 10]],
                'regular' => ['data' => [['id' => 21, 'name' => 'Ice Regular', 'max_level' => 15]]],
                'easier' => ['data' => [['id' => 11, 'name' => 'Ice Easier', 'max_level' => 8]]],
            ],
            'Chilled Hollow' => [
                'data' => [['id' => 31, 'name' => 'Chilled Wraith', 'max_level' => 22]],
            ],
        ]);

        $this->seedSpecialLocationCache([]);

        $pos = $character->map;

        Location::factory()->create([
            'name' => 'Chilled Hollow',
            'x' => $pos->character_position_x,
            'y' => $pos->character_position_y,
            'game_map_id' => $pos->game_map_id,
            'enemy_strength_increase' => 0.25,
        ]);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 21, 'name' => 'Ice Regular', 'max_level' => 15],
        ], $data);
    }

    public function test_ice_plane_regular_with_purgatory_without_location()
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap(['name' => MapNameValue::ICE_PLANE])->id,
        ]);

        $character = $this->givePurgatoryAccess($character);

        $mapName = $character->map->gameMap->name;

        $this->seedMonstersCache([
            $mapName => [
                'data' => [['id' => 1, 'name' => 'Ice Base', 'max_level' => 10]],
                'regular' => ['data' => [['id' => 21, 'name' => 'Ice Regular', 'max_level' => 15]]],
                'easier' => ['data' => [['id' => 11, 'name' => 'Ice Easier', 'max_level' => 8]]],
            ],
        ]);

        $this->seedSpecialLocationCache([]);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 21, 'name' => 'Ice Regular', 'max_level' => 15],
        ], $data);
    }

    public function test_ice_plane_easier_without_purgatory_without_location()
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap(['name' => MapNameValue::ICE_PLANE])->id,
        ]);

        $character = $character->refresh();

        $mapName = $character->map->gameMap->name;

        $this->seedMonstersCache([
            $mapName => [
                'data' => [['id' => 1, 'name' => 'Ice Base', 'max_level' => 10]],
                'regular' => ['data' => [['id' => 21, 'name' => 'Ice Regular', 'max_level' => 15]]],
                'easier' => ['data' => [['id' => 11, 'name' => 'Ice Easier', 'max_level' => 8]]],
            ],
        ]);

        $this->seedSpecialLocationCache([]);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 11, 'name' => 'Ice Easier', 'max_level' => 8],
        ], $data);
    }

    public function test_delusional_memories_regular_with_purgatory()
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap(['name' => MapNameValue::DELUSIONAL_MEMORIES])->id,
        ]);

        $character = $this->givePurgatoryAccess($character);

        $mapName = $character->map->gameMap->name;

        $this->seedMonstersCache([
            $mapName => [
                'data' => [['id' => 1, 'name' => 'DM Base', 'max_level' => 10]],
                'regular' => ['data' => [['id' => 21, 'name' => 'DM Regular', 'max_level' => 15]]],
                'easier' => ['data' => [['id' => 11, 'name' => 'DM Easier', 'max_level' => 8]]],
            ],
        ]);

        $this->seedSpecialLocationCache([]);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 21, 'name' => 'DM Regular', 'max_level' => 15],
        ], $data);
    }

    public function test_delusional_memories_easier_without_purgatory()
    {
        $character = $this->characterFactory->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap(['name' => MapNameValue::DELUSIONAL_MEMORIES])->id,
        ]);

        $character = $character->refresh();

        $mapName = $character->map->gameMap->name;

        $this->seedMonstersCache([
            $mapName => [
                'data' => [['id' => 1, 'name' => 'DM Base', 'max_level' => 10]],
                'regular' => ['data' => [['id' => 21, 'name' => 'DM Regular', 'max_level' => 15]]],
                'easier' => ['data' => [['id' => 11, 'name' => 'DM Easier', 'max_level' => 8]]],
            ],
        ]);

        $this->seedSpecialLocationCache([]);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 11, 'name' => 'DM Easier', 'max_level' => 8],
        ], $data);
    }

    public function test_special_location_type_overrides_everything()
    {
        $character = $this->characterFactory->getCharacter();

        $mapName = $character->map->gameMap->name;

        $this->seedMonstersCache([
            $mapName => [
                'data' => [['id' => 1, 'name' => 'Map Base', 'max_level' => 10]],
                'regular' => ['data' => [['id' => 21, 'name' => 'Map Regular', 'max_level' => 15]]],
                'easier' => ['data' => [['id' => 11, 'name' => 'Map Easier', 'max_level' => 8]]],
            ],
        ]);

        $this->seedSpecialLocationCache([
            'location-type-1' => [
                'data' => [['id' => 41, 'name' => 'Outpost Guard', 'max_level' => 30]],
            ],
        ]);

        $pos = $character->map;

        Location::factory()->create([
            'name' => 'Any Name',
            'x' => $pos->character_position_x,
            'y' => $pos->character_position_y,
            'game_map_id' => $pos->game_map_id,
            'type' => 1,
        ]);

        $response = $this->service->getMonstersForCharacter($character);

        $data = $this->unwrap($response);

        $this->assertEquals([
            ['id' => 41, 'name' => 'Outpost Guard', 'max_level' => 30],
        ], $data);
    }

    private function seedMonstersCache(array $payload): void
    {
        Cache::put('monsters', $payload, 60);
    }

    private function seedSpecialLocationCache(array $payload): void
    {
        Cache::put('special-location-monsters', $payload, 60);
    }

    private function givePurgatoryAccess($character)
    {
        $slots = new Collection([
            ['item' => ['effect' => ItemEffectsValue::PURGATORY]],
        ]);

        $inventory = new class($slots)
        {
            public Collection $slots;

            public function __construct(Collection $slots)
            {
                $this->slots = $slots;
            }
        };

        $character->setRelation('inventory', $inventory);

        return $character;
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
}
