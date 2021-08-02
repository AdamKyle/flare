<?php

namespace Tests\Unit\Admin\Jobs;

use App\Admin\Jobs\UpdateKingdomBuilding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateKingdom;

class UpdateKingdomBuildingTest extends TestCase
{
    use RefreshDatabase, CreateKingdom, CreateGameBuilding, CreateGameMap;

    public function testKingdomBuildingGetsUpdated()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => $this->createGameMap()->id,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameBuilding()->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 2,
            'max_defence'        => 100,
            'max_durability'     => 100,
            'current_durability' => 100,
            'current_defence'    => 100,
        ]);

        $kingdom  = $kingdom->refresh();
        $building = $kingdom->buildings->first();

        UpdateKingdomBuilding::dispatch($building, $building->gameBuilding);

        $building = $kingdom->buildings->first()->refresh();

        $this->assertEquals($building->current_defence,  300);
        $this->assertEquals($building->current_durability, 300);
        $this->assertEquals($building->max_defence, 300);
        $this->assertEquals($building->max_durability, 300);
    }
}
