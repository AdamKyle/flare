<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Game\Automation\Services\ExplorationCreatureCountCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ExplorationCreatureCountCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character;

    private ?ExplorationCreatureCountCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $this->calculator = resolve(ExplorationCreatureCountCalculator::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->calculator = null;
    }

    public function test_zero_fight_timeout_modifier_returns_six_creatures(): void
    {
        $this->assertEquals(6, $this->calculator->calculate($this->character));
    }

    public function test_half_fight_timeout_modifier_returns_eight_creatures(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 0.5,
        ]);

        $this->assertEquals(8, $this->calculator->calculate($this->character));
    }

    public function test_full_fight_timeout_modifier_returns_twelve_creatures(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 1,
        ]);

        $this->assertEquals(12, $this->calculator->calculate($this->character));
    }

    public function test_creature_count_has_minimum_of_six(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => -1,
        ]);

        $this->assertEquals(6, $this->calculator->calculate($this->character));
    }

    public function test_creature_count_has_maximum_of_twelve(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 2,
        ]);

        $this->assertEquals(12, $this->calculator->calculate($this->character));
    }

    public function test_decimal_fight_timeout_modifier_returns_floored_creature_count(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 0.75,
        ]);

        $this->assertEquals(9, $this->calculator->calculate($this->character));
    }
}
