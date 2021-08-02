<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\GameMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateKingdomBuilding;

class BuildingInQueueTest extends TestCase
{
    use RefreshDatabase,
        CreateKingdom,
        CreateKingdomBuilding,
        CreateGameBuilding;

    public function testGetCharacter() {

        $this->createBuildingQueue();

        $buildingInQueue = BuildingInQueue::first();

        $this->assertNotNull($buildingInQueue->character);
    }

    public function testGetKingdom() {

        $this->createBuildingQueue();

        $buildingInQueue = BuildingInQueue::first();

        $this->assertNotNull($buildingInQueue->kingdom);
    }

    protected function createBuildingQueue() {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        $kingdom = $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $building = $this->createKingdomBuilding([
            'game_building_id'   => $this->createGameBuilding()->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $this->createKingdomBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id'   => $kingdom->id,
            'building_id'  => $building->id,
            'to_level'     => 2,
            'completed_at' => now()->addMinutes(150)
        ]);
    }
}
