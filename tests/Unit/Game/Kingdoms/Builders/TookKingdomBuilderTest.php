<?php

namespace Tests\Unit\Game\Kingdoms\Builders;

use App\Game\Kingdoms\Builders\TookKingdomBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Builders\KingdomAttackedBuilder;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;

class TookKingdomBuilderTest extends TestCase {

    use RefreshDatabase, CreateKingdom, CreateGameUnit, CreateGameBuilding;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testUnitAmountAdded() {
        $kingdom = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom();

        $unitLog = $this->createLog($kingdom->getCharacter(), $kingdom->getKingdom(), 100, 50, 25);
        $log     = $this->createKingdomLog($unitLog);

        $attackLogBuilder = (new TookKingdomBuilder())->setLog($log);

        $changes = $attackLogBuilder->fetchChanges();

        $this->assertNotEmpty($changes['units']);
    }

    public function testUnitAmountGiven() {
        $kingdom = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom();

        $unitLog = $this->createLog($kingdom->getCharacter(), $kingdom->getKingdom(), 100, 50, 25);

        $unitLog['old_defender']['units'] = [];

        $log     = $this->createKingdomLog($unitLog);

        $attackLogBuilder = (new TookKingdomBuilder())->setLog($log);

        $changes = $attackLogBuilder->fetchChanges();

        $this->assertNotEmpty($changes['units']);
    }

    protected function createLog(Character $character, Kingdom $kingdom, int $sentAmount = 100, int $unitsSurvived = 0, int $newBuildingDur = 0) {
        $gameUnit = $this->createGameUnit();
        $gameBuilding = $this->createGameBuilding();

        return [
            'character_id'    => $character->id,
            'from_kingdom_id' => $kingdom->id,
            'to_kingdom_id'   => $kingdom->id,
            'status'          => KingdomLogStatusValue::TAKEN,
            'units_sent'      => [
                [
                    'unit_id'        => $gameUnit->id,
                    'amount'         => $sentAmount,
                    'total_attack'   => 1,
                    'total_defence'  => 1,
                    'settler'        => false,
                    'primary_target' => 'Walls',
                    'fall_back'      => 'Farms',
                ]
            ],
            'old_defender'    => [
                'units' => [
                    [
                        'game_unit_id'   => $gameUnit->id,
                        'amount'         => $sentAmount,
                        'total_attack'   => 1,
                        'total_defence'  => 1,
                        'settler'        => false,
                        'primary_target' => 'Walls',
                        'fall_back'      => 'Farms',
                    ]
                ],
                'buildings' => [
                    [
                        'current_durability' => 100,
                        'name'               => $gameBuilding->name,
                        'game_building'      => $gameBuilding->toArray(),
                    ]
                ]
            ],
            'new_defender'    => [
                'buildings' => [
                    [
                        'current_durability' => $newBuildingDur,
                        'name'               => $gameBuilding->name,
                        'game_building'      => $gameBuilding->toArray(),
                    ]
                ],
                'units' => [
                    [
                        'game_unit_id'   => $gameUnit->id,
                        'amount'         => $unitsSurvived,
                        'total_attack'   => 1,
                        'total_defence'  => 1,
                        'settler'        => false,
                        'primary_target' => 'Walls',
                        'fall_back'      => 'Farms',
                    ]
                ],
            ],
            'published'       => true,
            'created_at'      => now(),
        ];
    }
}
