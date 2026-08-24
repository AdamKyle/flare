<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight;

use App\Game\Core\Chance\ChanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\EntranceFactory;
use Tests\TestCase;

class EntranceTest extends TestCase
{
    use RefreshDatabase;

    public function test_getters_reflect_default_state(): void
    {
        $entrance = (new EntranceFactory())->build();

        $this->assertFalse($entrance->isCharacterEntracned());
        $this->assertFalse($entrance->isEnemyEntranced());
    }

    public function test_attacker_entrances_defender_when_effect_cant_be_resisted(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();

        $entrance->attackerEntrancesDefender($character, [
            'affixes' => ['entrancing_chance' => 0.5, 'cant_be_resisted' => true],
        ], false);

        $this->assertTrue($entrance->isEnemyEntranced());
    }

    public function test_attacker_entrances_defender_when_chance_is_above_one(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();

        $entrance->attackerEntrancesDefender($character, [
            'affixes' => ['entrancing_chance' => 2.0, 'cant_be_resisted' => false],
        ], false);

        $this->assertTrue($entrance->isEnemyEntranced());
    }

    public function test_attacker_fails_to_entrance_defender_when_the_chance_calculator_denies_it(): void
    {
        $factory = new EntranceFactory();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(false);

        $entrance = $factory->build($chanceCalculator);
        $character = $factory->buildCharacter();

        $entrance->attackerEntrancesDefender($character, [
            'affixes' => ['entrancing_chance' => 0.5, 'cant_be_resisted' => false],
        ], false);

        $this->assertFalse($entrance->isEnemyEntranced());
    }

    public function test_attacker_entrances_defender_uses_the_chance_calculator_on_the_normal_path(): void
    {
        $factory = new EntranceFactory();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $entrance = $factory->build($chanceCalculator);
        $character = $factory->buildCharacter();

        $entrance->attackerEntrancesDefender($character, [
            'affixes' => ['entrancing_chance' => 0.5, 'cant_be_resisted' => false],
        ], false);

        $this->assertTrue($entrance->isEnemyEntranced());
    }

    public function test_attacker_entrances_defender_skips_the_effect_when_entrancing_chance_is_zero(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();

        $entrance->attackerEntrancesDefender($character, [
            'affixes' => ['entrancing_chance' => 0.0, 'cant_be_resisted' => false],
        ], false);

        $this->assertFalse($entrance->isEnemyEntranced());
    }

    public function test_attacker_entrances_defender_skips_the_effect_when_attacker_is_voided(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();

        $entrance->attackerEntrancesDefender($character, [
            'affixes' => ['entrancing_chance' => 0.5, 'cant_be_resisted' => true],
        ], true);

        $this->assertFalse($entrance->isEnemyEntranced());
    }

    public function test_player_entrance_when_effect_cant_be_resisted(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();
        $monster = $factory->buildMonster();

        $entrance->playerEntrance($character, $monster, [
            'affixes' => ['entrancing_chance' => 0.5, 'cant_be_resisted' => true],
        ]);

        $this->assertTrue($entrance->isEnemyEntranced());
    }

    public function test_player_entrance_when_chance_is_above_one(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();
        $monster = $factory->buildMonster();

        $entrance->playerEntrance($character, $monster, [
            'affixes' => ['entrancing_chance' => 2.0, 'cant_be_resisted' => false],
        ]);

        $this->assertTrue($entrance->isEnemyEntranced());
    }

    public function test_player_entrance_fails_when_chance_is_at_or_below_negative_one(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();
        $monster = $factory->buildMonster(['affix_resistance' => 2.0]);

        $entrance->playerEntrance($character, $monster, [
            'affixes' => ['entrancing_chance' => 0.5, 'cant_be_resisted' => false],
        ]);

        $this->assertFalse($entrance->isEnemyEntranced());
    }

    public function test_player_entrance_uses_the_chance_calculator_on_the_normal_path(): void
    {
        $factory = new EntranceFactory();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(false);

        $entrance = $factory->build($chanceCalculator);
        $character = $factory->buildCharacter();
        $monster = $factory->buildMonster();

        $entrance->playerEntrance($character, $monster, [
            'affixes' => ['entrancing_chance' => 0.5, 'cant_be_resisted' => false],
        ]);

        $this->assertFalse($entrance->isEnemyEntranced());
    }

    public function test_player_entrance_skips_the_effect_when_entrancing_chance_is_zero(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();
        $monster = $factory->buildMonster();

        $entrance->playerEntrance($character, $monster, [
            'affixes' => ['entrancing_chance' => 0.0, 'cant_be_resisted' => false],
        ]);

        $this->assertFalse($entrance->isEnemyEntranced());
    }

    public function test_monster_entrances_player_when_chance_is_above_one(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();
        $monster = $factory->buildMonster(['entrance_chance' => 2.0]);

        $entrance->monsterEntrancesPlayer($character, $monster, false);

        $this->assertTrue($entrance->isCharacterEntracned());
    }

    public function test_monster_fails_to_entrance_player_when_chance_is_at_or_below_negative_one(): void
    {
        $factory = new EntranceFactory();
        $entrance = $factory->build();
        $character = $factory->buildCharacter();
        $monster = $factory->buildMonster(['entrance_chance' => -2.0]);

        $entrance->monsterEntrancesPlayer($character, $monster, false);

        $this->assertFalse($entrance->isCharacterEntracned());
    }

    public function test_monster_entrances_player_uses_the_default_difficulty_for_non_caster_classes(): void
    {
        $factory = new EntranceFactory();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $entrance = $factory->build($chanceCalculator);
        $character = $factory->buildCharacter(['name' => 'Fighter']);
        $monster = $factory->buildMonster(['entrance_chance' => 0.5]);

        $entrance->monsterEntrancesPlayer($character, $monster, false);

        $this->assertTrue($entrance->isCharacterEntracned());
    }

    public function test_monster_entrances_player_uses_focus_based_difficulty_for_prophets(): void
    {
        $factory = new EntranceFactory();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(false);

        $entrance = $factory->build($chanceCalculator);
        $character = $factory->buildCharacter(['name' => 'Prophet']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'focus' => 10,
            'voided_focus' => 10,
        ]);
        $monster = $factory->buildMonster(['entrance_chance' => 0.5]);

        $entrance->monsterEntrancesPlayer($character, $monster, false);

        $this->assertFalse($entrance->isCharacterEntracned());
    }

    public function test_monster_entrances_player_uses_focus_based_difficulty_for_heretics(): void
    {
        $factory = new EntranceFactory();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $entrance = $factory->build($chanceCalculator);
        $character = $factory->buildCharacter(['name' => 'Heretic']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'focus' => 10,
            'voided_focus' => 10,
        ]);
        $monster = $factory->buildMonster(['entrance_chance' => 0.5]);

        $entrance->monsterEntrancesPlayer($character, $monster, true);

        $this->assertTrue($entrance->isCharacterEntracned());
    }
}
