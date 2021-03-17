<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\User;
use App\Game\Kingdoms\Jobs\UpgradeKingdomBuilding;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameKingdomBuilding;
use Tests\Traits\CreateKingdom;

class UpgradeBuildingTest extends TestCase
{
    use RefreshDatabase, CreateKingdom, CreateGameKingdomBuilding;

    public function testJobReturnsEarlyWithNoQueue()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameKingdomBuilding()->id,
            'kingdom_id'        => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ]);

        UpgradeKingdomBuilding::dispatch(KingdomBuilding::first(), User::first(), 1);

        $this->assertTrue($kingdom->refresh()->buildings->first()->level === 1);
    }

    public function testUpgradeFarm()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameKingdomBuilding(['is_farm' => true])->id,
            'kingdom_id'        => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 300,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ]);

        $this->createKingdomBuildingQueue([
            'character_id' => 1,
            'kingdom_id'   => 1,
            'building_id'  => 1,
            'to_level'     => 2,
        ]);

        UpgradeKingdomBuilding::dispatch(KingdomBuilding::first(), User::first(), 1);
        
        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->buildings->first()->level === 2);
        $this->assertTrue($kingdom->max_population > 0);
    }

    public function testUpgradeKingdomBuildingWithInvalidResourceType()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => 1,
        ]);

        $kingdom->buildings()->create([
            'game_building_id'   => $this->createGameKingdomBuilding([
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
            'character_id' => 1,
            'kingdom_id'   => 1,
            'building_id'  => 1,
            'to_level'     => 2,
        ]);

        UpgradeKingdomBuilding::dispatch(KingdomBuilding::first(), User::first(), 1);
        
        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->buildings->first()->level === 1);
    }
}
