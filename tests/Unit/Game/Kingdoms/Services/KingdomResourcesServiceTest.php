<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Kingdoms\Values\KingdomMaxValue;
use Cache;
use Mockery;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameMap;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateNpc;
use Tests\Setup\Character\CharacterFactory;

class KingdomResourcesServiceTest extends TestCase {

    use RefreshDatabase, CreateKingdom, CreateGameBuilding, CreateNpc;

    public function testKingdomGetsUpdated() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale > .50);
        $this->assertTrue($kingdom->current_wood > 500);
        $this->assertTrue($kingdom->current_population > 0);
    }

    public function testKingdomGetsUpdatedTreasuryCannotGoPastMax() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => now(),
            'treasury'           => KingdomMaxValue::MAX_TREASURY
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale > .50);
        $this->assertTrue($kingdom->current_wood > 500);
        $this->assertTrue($kingdom->current_population > 0);
        $this->assertEquals(KingdomMaxValue::MAX_TREASURY, $kingdom->treasury);
    }

    public function testKingdomIsGivenToNpc() {

        $this->createNpc();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true);

        $kingdom = $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => null,
        ]);

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertNull($kingdom->character_id);
        $this->assertTrue($kingdom->npc_owned);
    }

    public function testKingdomIsGivenToNpcWhenOnline() {

        $this->createNpc();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true);

        $kingdom = $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => null,
        ]);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertNull($kingdom->character_id);
        $this->assertTrue($kingdom->npc_owned);
    }

    public function testKingdomIsGivenToNpcWhenLastWalkedIsGreatorThenFortyDays() {

        $this->createNpc();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true);

        $kingdom = $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => now()->subDays(50),
        ]);

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertNull($kingdom->character_id);
        $this->assertTrue($kingdom->npc_owned);
    }

    public function testKingdomLosesMoraleForNotBeingWalked() {

        $this->createNpc();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true);

        $kingdom = $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => now()->subDays(31),
            'current_morale'     => 1.0
        ]);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $character->user->update([
            'show_kingdom_update_messages' => true,
        ]);

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals(.90, $kingdom->current_morale);
    }

    public function testKingdomLosesMoraleForNotBeingWalkedAndIsGivenToNPC() {

        $this->createNpc();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true);

        $kingdom = $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => now()->subDays(31),
            'current_morale'     => 0
        ]);

        Cache::put('kingdoms-updated-' . $character->user->id, [[
            'id'         => $kingdom->id,
            'name'       => $kingdom->name,
            'x_position' => $kingdom->x_position,
            'y_position' => $kingdom->y_position,
            'color'      => $kingdom->color,
        ]]);

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->npc_owned);
    }

    public function testNpcKingdomIsDeletedAfterFiveDays() {

        $this->createNpc();

        $kingdom = $this->createKingdom([
            'character_id'       => null,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => now()->subDays(10),
            'npc_owned'          => true,
            'updated_at'         => now()->subDays(10)
        ]);

        $mock = Mockery::mock(KingdomResourcesService::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $mock->shouldReceive('removeKingdomFromMap')->andReturn(null);

        $this->app->instance(KingdomResourcesService::class, $mock);

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom->refresh())->updateKingdom();

        $this->assertTrue(true);
    }

    public function testKingdomGetsUpdatedWhenUserIsOnline() {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $characterFactory->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $kingdom = $this->createKingdom([
            'character_id'       => $characterFactory->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $user = $characterFactory->getUser();

        $user->update([
            'show_kingdom_update_messages' => true
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale > .50);
        $this->assertTrue($kingdom->current_wood > 500);
    }

    public function testKingdomMaxPopulationGetsSetAsCurrentPopulation() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'max_population'     => 1,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale > .50);
        $this->assertEquals($kingdom->current_population, 1);
    }

    public function testKingdomMaxPopulationGetsSetAsPartialCurrentPopulation() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'max_population'     => 10,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale > .50);
        $this->assertEquals($kingdom->current_population, 3);
    }

    public function testKingdomMaxPopulationGetsSetAsMaxCurrentPopulation() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'max_population'     => 1,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale > .50);
        $this->assertEquals($kingdom->current_population, 1);
    }



    public function testKingdomMaxResourceGetsSetAsCurrentResourse() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 0,
            'max_wood'           => 1,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100
                ])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale > .50);
        $this->assertEquals($kingdom->current_wood, 1);
    }

    public function testKingdomNoResourcesGetsSetAsCurrentResourse() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 0,
            'max_wood'           => 1,
            'last_walked'        => now(),
            'current_morale'     => 0,
            'treasury'           => 0,
            'name'               => 'Apple Sauce'
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100
                ])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 0,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals($kingdom->current_wood, 0);
    }

    public function testKingdomDecreasesMorale() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => 0.10,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100,
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'               => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale < .50);
        $this->assertFalse($kingdom->current_wood < 500);
        $this->assertTrue($kingdom->current_population > 0);
    }

    public function testKingdomAdjustMorale() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100,
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals($kingdom->current_morale, .45);
        $this->assertEquals($kingdom->current_wood, 700);
        $this->assertTrue($kingdom->current_population > 0);
    }

    public function testKingdomDoNotAddMorale() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => 1.0,
            'max_population'     => 1,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameBuilding([
                    'is_farm'                => true,
                    'increase_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 100,
                'current_defence'        => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building'   => true,
                    'increase_wood_amount'   => 100,
                    'increase_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 0,
                'current_defence'        => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals($kingdom->current_morale, 1.0);
        $this->assertEquals($kingdom->current_population, 1);
    }

    public function testKingdomSetMoraleToOne() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => 0,
            'max_population'     => 1,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameBuilding([
                    'is_farm'                => true,
                    'increase_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 100,
                'current_defence'        => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building'   => true,
                    'increase_wood_amount'   => 100,
                    'increase_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 100,
                'current_defence'        => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name'                   => 'Keep',
                    'increase_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 100,
                'current_defence'        => 100,
            ],
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals($kingdom->current_morale, 1.0);
        $this->assertEquals($kingdom->current_population, 1);
    }

    public function testKingdomDoNotReduceMorale() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => 0,
            'max_population'     => 1,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameBuilding([
                    'is_farm'                => true,
                    'decrease_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 0,
                'current_defence'        => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building'   => true,
                    'increase_wood_amount'   => 100,
                    'decrease_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 0,
                'current_defence'        => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals($kingdom->current_morale, 0);
    }

    public function testKingdomDoNotReduceMoraleBelowZero() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => .50,
            'max_population'     => 1,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameBuilding([
                    'is_farm'                => true,
                    'decrease_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 0,
                'current_defence'        => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building'   => true,
                    'increase_wood_amount'   => 100,
                    'decrease_morale_amount' => 2.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 0,
                'current_defence'        => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals($kingdom->current_morale, 0);
    }

    public function testKingdomDoNotAdjustMoraleBelowZero() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => .50,
            'max_population'     => 1,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameBuilding([
                    'is_farm'                => true,
                    'decrease_morale_amount' => 6.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 0,
                'current_defence'        => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building'   => true,
                    'increase_wood_amount'   => 100,
                    'increase_morale_amount' => .05,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 100,
                'current_defence'        => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals($kingdom->current_morale, 0);
    }

    public function testDoNotUpdateTreasureyWhenKingdomMoraleIsZero() {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $characterFactory->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $kingdom = $this->createKingdom([
            'character_id'       => $characterFactory->getCharacter(true)->id,
            'game_map_id'        => GameMap::first()->id,
            'current_wood'       => 500,
            'current_morale'     => 0,
            'treasury'           => 1000,
            'last_walked'        => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameBuilding([
                    'is_farm'                => true,
                    'decrease_morale_amount' => 6.0,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 0,
                'current_defence'        => 100,

            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'is_resource_building'   => true,
                    'increase_wood_amount'   => 100,
                    'increase_morale_amount' => .05,
                ])->id,
                'kingdom_id'            => $kingdom->id,
                'level'                  => 1,
                'max_defence'            => 100,
                'max_durability'         => 100,
                'current_durability'     => 0,
                'current_defence'        => 100,
            ],
            [
                'game_building_id'     => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id'          => $kingdom->id,
                'level'                => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resourceService = resolve(KingdomResourcesService::class);

        $resourceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals(1000, $kingdom->treasury);
    }
}
