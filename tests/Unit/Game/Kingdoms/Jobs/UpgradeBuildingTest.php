<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\User;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;

class UpgradeBuildingTest extends TestCase
{
    use RefreshDatabase, CreateKingdom, CreateGameBuilding;

    public function testJobReturnsEarlyWithNoQueue()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => GameMap::first()->id,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameBuilding()->id,
            'kingdom_id'        => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ]);

        UpgradeBuilding::dispatch(KingdomBuilding::first(), User::first(), 1);

        $this->assertTrue($kingdom->refresh()->buildings->first()->level === 1);
    }

    public function testUpgradeFarm()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => GameMap::first()->id,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameBuilding(['is_farm' => true])->id,
            'kingdom_id'          => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ]);

        $queue = $this->createKingdomBuildingQueue([
            'character_id' => Character::first()->id,
            'kingdom_id'   => $kingdom->id,
            'building_id'  => $kingdom->buildings->first()->id,
            'to_level'     => 2,
        ]);

        UpgradeBuilding::dispatch($kingdom->buildings->first(), User::first(), $queue->id);

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->buildings->first()->level === 2);
        $this->assertTrue($kingdom->max_population > 0);
    }

    public function testUpgradeKingdomBuildingWithInvalidResourceType()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => GameMap::first()->id,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameBuilding([
                'increase_wood_amount'  => 0,
                'increase_clay_amount'  => 0,
                'increase_stone_amount' => 0,
                'increase_iron_amount'  => 0,
                'is_resource_building'  => true,
            ])->id,
            'kingdom_id'        => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ]);

        $this->createKingdomBuildingQueue([
            'character_id' => Character::first()->id,
            'kingdom_id'   => $kingdom->id,
            'building_id'  => $kingdom->buildings->first()->id,
            'to_level'     => 2,
        ]);

        UpgradeBuilding::dispatch(KingdomBuilding::first(), User::first(), 1);

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->buildings->first()->level === 1);
    }
}
