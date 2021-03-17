<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameKingdomBuilding;
use Tests\Traits\CreateKingdom;

class KingdomResourcesServiceTest extends TestCase {

    use RefreshDatabase, CreateKingdom, CreateGameKingdomBuilding;

    public function testkingdomGetsUpdated() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameKingdomBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameKingdomBuilding([
                    'is_resource_building' => true, 
                    'increase_wood_amount' => 100
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

    public function testkingdomGetsUpdatedWhenUserIsOnline() {
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
            'character_id'       => $characterFactory->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameKingdomBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameKingdomBuilding([
                    'is_resource_building' => true, 
                    'increase_wood_amount' => 100
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
    }

    public function testKingdomMaxPopulationGetsSetAsCurrentPopulation() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
            'max_population'     => 1,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameKingdomBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameKingdomBuilding([
                    'is_resource_building' => true, 
                    'increase_wood_amount' => 100
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
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_wood'       => 0,
            'max_wood'           => 1,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameKingdomBuilding([
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
                'game_building_id'   => $this->createGameKingdomBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,
            ],
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_morale > .50);
        $this->assertEquals($kingdom->current_wood, 1);
    }

    public function testKingdomDecreasesMorale() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameKingdomBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 0,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameKingdomBuilding([
                    'is_resource_building' => true, 
                    'increase_wood_amount' => 100,
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

        $this->assertTrue($kingdom->current_morale < .50);
        $this->assertFalse($kingdom->current_wood > 500);
        $this->assertFalse($kingdom->current_population > 0);
    }

    public function testKingdomAdjustMorale() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'   => $this->createGameKingdomBuilding(['is_farm' => true])->id,
                'kingdom_id'        => $kingdom->id,
                'level'              => 1,
                'max_defence'        => 100,
                'max_durability'     => 100,
                'current_durability' => 100,
                'current_defence'    => 100,

            ],
            [
                'game_building_id'     => $this->createGameKingdomBuilding([
                    'is_resource_building' => true, 
                    'increase_wood_amount' => 100,
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

        $this->assertEquals($kingdom->current_morale, .50);
        $this->assertEquals($kingdom->current_wood, 500);
        $this->assertTrue($kingdom->current_population > 0);
    }

    public function testKingdomDoNotAddMorale() {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => 1.0,
            'max_population'     => 1,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameKingdomBuilding([
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
                'game_building_id'     => $this->createGameKingdomBuilding([
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
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => 0,
            'max_population'     => 1,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameKingdomBuilding([
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
                'game_building_id'     => $this->createGameKingdomBuilding([
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
            ]
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
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => 0,
            'max_population'     => 1,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameKingdomBuilding([
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
                'game_building_id'     => $this->createGameKingdomBuilding([
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
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => .50,
            'max_population'     => 1,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameKingdomBuilding([
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
                'game_building_id'     => $this->createGameKingdomBuilding([
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
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
            'current_morale'     => .50,
            'max_population'     => 1,
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id'       => $this->createGameKingdomBuilding([
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
                'game_building_id'     => $this->createGameKingdomBuilding([
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
            ]
        ]);

        $kingdom = $kingdom->refresh();

        $resouceService = resolve(KingdomResourcesService::class);

        $resouceService->setKingdom($kingdom)->updateKingdom();

        $kingdom = $kingdom->refresh();

        $this->assertEquals($kingdom->current_morale, 0);
    }
}