<?php

namespace Tests\Unit\Game\Battle\ServerFight\Monster;

use App\Game\Battle\ServerFight\Monster\ServerMonster;
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

    public function test_devouring_darkness_fails_when_resistance_exceeds_the_chance(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $monster = (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster([
            'devouring_darkness_chance' => 0.25,
        ]);

        $this->assertFalse($monster->canMonsterDevoidPlayer(0.5));
    }

    public function test_devouring_light_fails_when_resistance_exceeds_the_chance(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $monster = (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster([
            'devouring_light_chance' => 0.25,
        ]);

        $this->assertFalse($monster->canMonsterVoidPlayer(0.5));
    }

    public function test_get_id_returns_the_monster_id(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $monster = (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster([
            'id' => 42,
        ]);

        $this->assertSame(42, $monster->getId());
    }

    public function test_can_monster_use_elemental_attack_reflects_all_atonements_being_positive(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $monster = (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster([
            'fire_atonement' => 0.5,
            'ice_atonement' => 0.5,
            'water_atonement' => 0.5,
        ]);

        $this->assertTrue($monster->canMonsterUseElementalAttack());
    }

    public function test_can_monster_use_elemental_attack_is_false_when_any_atonement_is_not_positive(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);

        $monster = (new ServerMonster(new ChanceCalculator($randomNumberGenerator), $randomNumberGenerator))->setMonster([
            'fire_atonement' => 0.0,
            'ice_atonement' => 0.5,
            'water_atonement' => 0.5,
        ]);

        $this->assertFalse($monster->canMonsterUseElementalAttack());
    }
}
