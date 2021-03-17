<?php

namespace Tests\Unit\Admin\Jobs;

use App\Admin\Jobs\UpdateKingdomBuilding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameKingdomBuilding;
use Tests\Traits\CreateKingdom;

class UpdateKingdomBuildingTest extends TestCase
{
    use RefreshDatabase, CreateKingdom, CreateGameKingdomBuilding;

    public function testKingdomBuildingGetsUpdated()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameKingdomBuilding()->id,
            'kingdom_id'        => $kingdom->id,
            'level'              => 2,
            'max_defence'        => 100,
            'max_durability'     => 100,
            'current_durability' => 100,
            'current_defence'    => 100,
        ]);

        $kingdom  = $kingdom->refresh();
        $building = $kingdom->buildings->first();

        UpdateKingdomBuilding::dispatch($building);

        $building = $kingdom->buildings->first()->refresh();

        $this->assertTrue($building->current_defence > 100);
        $this->assertTrue($building->current_durability > 100);
        $this->assertTrue($building->max_defence > 100);
        $this->assertTrue($building->max_durability > 100);
    }
}
