<?php

namespace Tests\Unit\Game\Battle\ServerFight\Monster;

use App\Game\Battle\ServerFight\Monster\ServerMonster;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\BuildMonsterFactory;
use Tests\TestCase;

class BuildMonsterTest extends TestCase
{
    use RefreshDatabase;

    private BuildMonsterFactory $buildMonsterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildMonsterFactory = new BuildMonsterFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->buildMonsterFactory);
    }

    public function test_set_server_monster_delegates_to_the_server_monster_collaborator(): void
    {
        $buildMonster = $this->buildMonsterFactory->buildBuildMonster();

        $result = $buildMonster->setServerMonster([
            'name' => 'Test Monster',
            'only_for_location_type' => null,
            'health_range' => '100-100',
            'increases_damage_by' => null,
            'accuracy' => 0.5,
            'casting_accuracy' => 0.5,
            'dodge' => 0.5,
            'criticality' => 0.5,
            'spell_evasion' => 0.5,
            'affix_resistance' => 0.5,
            'counter_resistance_chance' => 0.5,
            'ambush_resistance_chance' => 0.5,
            'str' => 100,
            'int' => 100,
            'dex' => 100,
            'dur' => 100,
            'agi' => 100,
            'chr' => 100,
            'focus' => 100,
        ]);

        $this->assertInstanceOf(ServerMonster::class, $result);
        $this->assertSame('Test Monster', $result->getName());
    }

    public function test_build_monster_assembles_the_full_pipeline_and_sets_health(): void
    {
        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with('100', '100')->andReturn(100);

        $buildMonster = $this->buildMonsterFactory->buildBuildMonster(randomNumberGenerator: $randomNumberGenerator);

        $result = $buildMonster->buildMonster([
            'name' => 'Test Monster',
            'only_for_location_type' => null,
            'health_range' => '100-100',
            'increases_damage_by' => null,
            'accuracy' => 0.5,
            'casting_accuracy' => 0.5,
            'dodge' => 0.5,
            'criticality' => 0.5,
            'spell_evasion' => 0.5,
            'affix_resistance' => 0.5,
            'counter_resistance_chance' => 0.5,
            'ambush_resistance_chance' => 0.5,
            'str' => 100,
            'int' => 100,
            'dex' => 100,
            'dur' => 100,
            'agi' => 100,
            'chr' => 100,
            'focus' => 100,
        ], [
            'all_stat_reduction' => null,
            'stat_reduction' => [],
            'cant_be_resisted' => true,
        ], 0.0, 0.0);

        $this->assertSame(100, $result->getHealth());
    }

    public function test_can_monster_have_stats_reduced_is_always_true_when_the_affix_cant_be_resisted(): void
    {
        $buildMonster = $this->buildMonsterFactory->buildBuildMonster();

        $this->assertTrue($buildMonster->canMonsterHaveStatsReduced([
            'affix_resistance' => 0.5,
        ], 0.0, true));
    }

    public function test_can_monster_have_stats_reduced_is_true_when_the_chance_exceeds_one(): void
    {
        $buildMonster = $this->buildMonsterFactory->buildBuildMonster();

        $this->assertTrue($buildMonster->canMonsterHaveStatsReduced([
            'affix_resistance' => 3.0,
        ], 0.0, false));
    }

    public function test_can_monster_have_stats_reduced_uses_the_chance_calculator_on_the_normal_path(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(false);

        $buildMonster = $this->buildMonsterFactory->buildBuildMonster($chanceCalculator);

        $this->assertFalse($buildMonster->canMonsterHaveStatsReduced([
            'affix_resistance' => 0.5,
        ], 0.0, false));
    }

    public function test_can_monster_have_stats_reduced_caps_the_difficulty_at_ninety_nine(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->with(1.0)->andReturn(true);

        $buildMonster = $this->buildMonsterFactory->buildBuildMonster($chanceCalculator);

        $this->assertTrue($buildMonster->canMonsterHaveStatsReduced([
            'affix_resistance' => 0.99,
        ], 0.0, false));
    }
}
