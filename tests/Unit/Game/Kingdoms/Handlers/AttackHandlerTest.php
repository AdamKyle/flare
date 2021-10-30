<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use App\Game\Kingdoms\Handlers\AttackHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttackHandlerTest extends TestCase {

    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testCalculatePercentageWithOutHealers() {

        $attackHandler = new AttackHandler();

        $percentage = $attackHandler->calculateNewPercentageOfAttackersLost(5.0, []);

        $this->assertEquals(5.0, $percentage);
    }

    public function testCalculatePercentageWithHealers() {
        $unitsToAttack[] = [
            'amount'         => 1000,
            'total_attack'   => 0,
            'total_defence'  => 0,
            'unit_id'        => 1,
            'settler'        => false,
            'healer'         => true,
            'heal_for'       => 10,
        ];

        $attackHandler = new AttackHandler();

        $percentage = $attackHandler->calculateNewPercentageOfAttackersLost(5.0, $unitsToAttack);

        $this->assertEquals(0, $percentage);
    }
}
