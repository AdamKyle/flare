<?php

namespace Tests\Unit\Game\Kingdoms\Builders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Builders\AttackedKingdomBuilder;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;

class AttackedKingdomBuilderTest extends TestCase {

    use RefreshDatabase, CreateKingdom, CreateGameUnit;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testLostAll() {
        $kingdom = (new CharacterFactory())->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->kingdomManagement()
                                           ->assignKingdom();

        $unitLog = $this->createUnitsLog($kingdom->getCharacter(), $kingdom->getKingdom());
        $log     = $this->createKingdomLog($unitLog);

        $attackLogBuilder = (new AttackedKingdomBuilder())->setLog($log);

        $changes = $attackLogBuilder->attackedKingdomReport();

        $this->assertNotEmpty($changes);
    }

    public function testLostSomeWithHealers() {
        $kingdom = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom();

        $unitLog = $this->createUnitsLog($kingdom->getCharacter(), $kingdom->getKingdom(), 100, 25, 5, true);
        $log     = $this->createKingdomLog($unitLog);

        $attackLogBuilder = (new AttackedKingdomBuilder())->setLog($log);

        $changes = $attackLogBuilder->attackedKingdomReport();

        $this->assertNotEmpty($changes);

        foreach ($changes as $key => $value) {
            $this->assertGreaterThan(0, $value['total_heal']);
        }
    }

    protected function createUnitsLog(Character $character, Kingdom $kingdom, int $sentAmount = 100, int $unitsSurvived = 0, int $healFor = 0, bool $healer = false) {
        $gameUnit = $this->createGameUnit();

        return [
            'character_id'    => $character->id,
            'from_kingdom_id' => $kingdom->id,
            'to_kingdom_id'   => $kingdom->id,
            'status'          => KingdomLogStatusValue::ATTACKED,
            'units_sent'      => [
                [
                    'unit_id'        => $gameUnit->id,
                    'amount'         => $sentAmount,
                    'total_attack'   => 1,
                    'total_defence'  => 1,
                    'settler'        => false,
                    'primary_target' => 'Walls',
                    'fall_back'      => 'Farms',
                    'healer'         => $healer,
                    'heal_for'       => $healFor,
                ]
            ],
            'units_survived'  => [
                [
                    'unit_id'        => $gameUnit->id,
                    'amount'         => $unitsSurvived,
                    'total_attack'   => 1,
                    'total_defence'  => 1,
                    'settler'        => false,
                    'primary_target' => 'Walls',
                    'fall_back'      => 'Farms',
                    'healer'         => $healer,
                    'heal_for'       => $healFor,
                ]
            ],
            'old_defender'    => $kingdom->toArray(),
            'new_defender'    => $kingdom->toArray(),
            'published'       => true,
            'created_at'      => now(),
        ];
    }
}
