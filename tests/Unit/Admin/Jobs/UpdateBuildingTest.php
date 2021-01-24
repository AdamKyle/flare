<?php

namespace Tests\Unit\Admin\Jobs;

use App\Admin\Jobs\UpdateBuilding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;

class UpdateBuildingTest extends TestCase
{
    use RefreshDatabase, CreateKingdom, CreateGameBuilding;

    public function testBuildingGetsUpdated()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
            'current_wood'       => 500,
            'current_population' => 0,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameBuilding()->id,
            'kingdoms_id'        => $kingdom->id,
            'level'              => 2,
            'max_defence'        => 100,
            'max_durability'     => 100,
            'current_durability' => 100,
            'current_defence'    => 100,
        ]);

        $kingdom  = $kingdom->refresh();
        $building = $kingdom->buildings->first();

        UpdateBuilding::dispatch($building);

        $building = $kingdom->buildings->first()->refresh();

        $this->assertTrue($building->current_defence > 100);
        $this->assertTrue($building->current_durability > 100);
        $this->assertTrue($building->max_defence > 100);
        $this->assertTrue($building->max_durability > 100);
    }
}
