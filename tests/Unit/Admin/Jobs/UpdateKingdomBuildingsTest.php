<?php

namespace Tests\Unit\Admin\Jobs;

use Mail;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Admin\Mail\GenericMail;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;

class UpdateKingdomBuildingsTest extends TestCase
{
    use RefreshDatabase, CreateKingdom, CreateGameBuilding, CreateGameUnit;
    

    public function testAddKingdomBuildingToKingdom()
    {
        Mail::fake();

        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $building = $this->createGameBuilding();

        UpdateKingdomBuildings::dispatch($building);

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->buildings->isNotEmpty());

        Mail::assertSent(GenericMail::class, 1);
    }

    public function testAddKingdomBuildingToKingdomWhenUserOnline()
    {
        Mail::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $kingdom = $this->createKingdom([
            'character_id'       => $character->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $building = $this->createGameBuilding();

        UpdateKingdomBuildings::dispatch($building);

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->buildings->isNotEmpty());

        Mail::assertNotSent(GenericMail::class);
    }

    public function testUpdateKingdomBuildingWhenKingdomHasKingdomBuildingWithUnits()
    {
        Mail::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $kingdom = $this->createKingdom([
            'character_id'       => $character->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $building = $this->createGameBuilding([
            'max_level' => 30
        ]);
        
        $unit = $this->createGameUnit();

        $building->units()->create([
            'game_building_id' => $building->id,
            'game_unit_id'     => $unit->id,
            'required_level'   => 1,
        ]);

        $kingdom->buildings()->create([
            'kingdom_id' => $kingdom->id,
            'game_building_id' => $building->id,
            'level' => 2,
            'current_defence' => 100,
            'current_durability' => 100,
            'max_defence' => 100,
            'max_durability' => 100,
        ]);

        UpdateKingdomBuildings::dispatch($building->refresh(), [1], 5);

        $kingdom  = $kingdom->refresh();
        $building = $kingdom->buildings->first();

        $this->assertTrue($building->current_defence > 100);
        $this->assertTrue($building->current_durability > 100);
        $this->assertTrue($building->max_defence > 100);
        $this->assertTrue($building->max_durability > 100);

        Mail::assertNotSent(GenericMail::class);
    }
}
