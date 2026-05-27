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

    private Character $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    public function testZeroFightTimeoutModifierReturnsSixCreatures(): void
    {
        $calculator = resolve(ExplorationCreatureCountCalculator::class);

        $this->assertEquals(6, $calculator->calculate($this->character));
    }

    public function testHalfFightTimeoutModifierReturnsEightCreatures(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 0.5,
        ]);

        $calculator = resolve(ExplorationCreatureCountCalculator::class);

        $this->assertEquals(8, $calculator->calculate($this->character));
    }

    public function testFullFightTimeoutModifierReturnsTwelveCreatures(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 1,
        ]);

        $calculator = resolve(ExplorationCreatureCountCalculator::class);

        $this->assertEquals(12, $calculator->calculate($this->character));
    }

    public function testCreatureCountHasMinimumOfSix(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => -1,
        ]);

        $calculator = resolve(ExplorationCreatureCountCalculator::class);

        $this->assertEquals(6, $calculator->calculate($this->character));
    }

    public function testCreatureCountHasMaximumOfTwelve(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 2,
        ]);

        $calculator = resolve(ExplorationCreatureCountCalculator::class);

        $this->assertEquals(12, $calculator->calculate($this->character));
    }

    public function testDecimalFightTimeoutModifierReturnsFlooredCreatureCount(): void
    {
        $skill = $this->character->skills->where('name', 'Fighters Timeout')->first();
        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 0.75,
        ]);

        $calculator = resolve(ExplorationCreatureCountCalculator::class);

        $this->assertEquals(9, $calculator->calculate($this->character));
    }
}
