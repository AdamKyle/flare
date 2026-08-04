<?php

namespace Tests\Unit\Flare\ServerFight\Monster;

use App\Flare\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;
use PHPUnit\Framework\TestCase;

class ServerMonsterTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_devouring_darkness_passes_at_the_preserved_percentage_threshold(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 10_000)->andReturn(2_500);

        $monster = (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster([
            'devouring_darkness_chance' => 0.25,
        ]);

        $this->assertTrue($monster->canMonsterDevoidPlayer(0.0));
    }

    public function test_devouring_light_fails_above_the_preserved_percentage_threshold(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 10_000)->andReturn(2_501);

        $monster = (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster([
            'devouring_light_chance' => 0.25,
        ]);

        $this->assertFalse($monster->canMonsterVoidPlayer(0.0));
    }

    public function test_attack_range_and_damage_modifier_are_preserved(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(10, 20)->andReturn(15);

        $monster = (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster([
            'attack_range' => '10-20',
            'increases_damage_by' => 1.0,
        ]);

        $this->assertSame(30, $monster->buildAttack());
    }
}
