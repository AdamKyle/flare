<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateKingdomBuilding;

class KingdomTransformerTest extends TestCase
{
    use RefreshDatabase,
        CreateKingdom,
        CreateKingdomBuilding,
        CreateGameBuilding,
        CreateGameUnit,
        CreateGameBuildingUnit;

    public function testTransformerWithRecruitableUnits() {
        $kingdom = $this->createTestKingdomWithRecruitableUnit();

        $manager            = new Manager();
        $kingdomTransfromer = resolve(KingdomTransformer::class);

        $kingdom = new Item($kingdom, $kingdomTransfromer);
        $kingdom = $manager->createData($kingdom)->toArray();

        $this->assertNotEmpty($kingdom['data']['recruitable_units']);
    }

    protected function createTestKingdomWithRecruitableUnit(): Kingdom {
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

        $gameBuilding = $this->createGameBuilding([
            'trains_units' => true
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $gameUnit = $this->createGameUnit();

        $this->createGameBuildingUnit([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id'     => $gameUnit->id,
            'required_level'   => 1,
        ]);

        $this->createKingdomUnit([
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount'       => 500,
        ]);

        return $kingdom;
    }
}
