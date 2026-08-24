<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight;

use App\Game\Core\Chance\ChanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\CanHitFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CanHitTest extends TestCase
{
    use RefreshDatabase;

    private CanHitFactory $canHitFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->canHitFactory = new CanHitFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->canHitFactory);
    }

    public function test_can_player_hit_monster_true_when_raid_boss_and_character_attack_below_minimum(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character);
        $monster = $this->canHitFactory->buildMonster(['is_raid_boss' => true]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canPlayerHitMonster($character, $monster, false));
    }

    public function test_can_player_hit_monster_true_when_character_accuracy_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['skills' => ['accuracy' => 1, 'casting_accuracy' => 0, 'dodge' => 0, 'criticality' => 0]]);
        $monster = $this->canHitFactory->buildMonster([]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canPlayerHitMonster($character, $monster, false));
    }

    public function test_can_player_hit_monster_false_when_enemy_dodge_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character);
        $monster = $this->canHitFactory->buildMonster(['dodge' => 1]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertFalse($canHit->canPlayerHitMonster($character, $monster, false));
    }

    public function test_can_player_hit_monster_follows_the_to_hit_formula_on_the_normal_path(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['str' => 20, 'str_modded' => 20]);
        $monster = $this->canHitFactory->buildMonster(['agi' => 5]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canPlayerHitMonster($character, $monster, false));
    }

    public function test_can_monster_hit_player_false_when_character_dodge_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['skills' => ['accuracy' => 0, 'casting_accuracy' => 0, 'dodge' => 1, 'criticality' => 0]]);
        $monster = $this->canHitFactory->buildMonster([]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertFalse($canHit->canMonsterHitPlayer($character, $monster, false));
    }

    public function test_can_monster_hit_player_true_when_monster_accuracy_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character);
        $monster = $this->canHitFactory->buildMonster(['accuracy' => 1]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canMonsterHitPlayer($character, $monster, false));
    }

    public function test_can_monster_hit_player_follows_the_to_hit_formula_on_the_normal_path(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['agi' => 5, 'agi_modded' => 5]);
        $monster = $this->canHitFactory->buildMonster(['to_hit_base' => 20, 'agi' => 5]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canMonsterHitPlayer($character, $monster, false));
    }

    public function test_can_player_cast_spell_true_when_raid_boss_and_character_attack_below_minimum(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character);
        $monster = $this->canHitFactory->buildMonster(['is_raid_boss' => true]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canPlayerCastSpell($character, $monster, false));
    }

    public function test_can_player_cast_spell_true_when_casting_accuracy_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['skills' => ['accuracy' => 0, 'casting_accuracy' => 1, 'dodge' => 0, 'criticality' => 0]]);
        $monster = $this->canHitFactory->buildMonster([]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canPlayerCastSpell($character, $monster, false));
    }

    public function test_can_player_cast_spell_false_when_enemy_dodge_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character);
        $monster = $this->canHitFactory->buildMonster(['dodge' => 1]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertFalse($canHit->canPlayerCastSpell($character, $monster, false));
    }

    public function test_can_player_cast_spell_follows_the_to_hit_formula_on_the_normal_path(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['str' => 20, 'str_modded' => 20]);
        $monster = $this->canHitFactory->buildMonster(['agi' => 5]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canPlayerCastSpell($character, $monster, false));
    }

    public function test_can_monster_cast_spell_false_when_character_dodge_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['skills' => ['accuracy' => 0, 'casting_accuracy' => 0, 'dodge' => 1, 'criticality' => 0]]);
        $monster = $this->canHitFactory->buildMonster([]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertFalse($canHit->canMonsterCastSpell($character, $monster, false));
    }

    public function test_can_monster_cast_spell_true_when_monster_casting_accuracy_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character);
        $monster = $this->canHitFactory->buildMonster(['casting_accuracy' => 1]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canMonsterCastSpell($character, $monster, false));
    }

    public function test_can_monster_cast_spell_follows_the_to_hit_formula_on_the_normal_path(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['agi' => 5, 'agi_modded' => 5]);
        $monster = $this->canHitFactory->buildMonster(['to_hit_base' => 20, 'agi' => 5]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canMonsterCastSpell($character, $monster, false));
    }

    public function test_can_player_auto_hit_false_when_character_is_not_a_thief(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter([], ['name' => 'Fighter'])->getCharacter();

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertFalse($canHit->canPlayerAutoHit($character));
    }

    public function test_can_player_auto_hit_false_when_thief_has_no_extra_action_item(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter([], ['name' => 'Thief'])->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['extra_action_chance' => ['has_item' => false, 'chance' => 0]]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertFalse($canHit->canPlayerAutoHit($character));
    }

    public function test_can_player_auto_hit_true_when_extra_action_chance_is_at_least_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter([], ['name' => 'Thief'])->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['extra_action_chance' => ['has_item' => true, 'chance' => 1]]);

        $canHit = $this->canHitFactory->buildCanHit();

        $this->assertTrue($canHit->canPlayerAutoHit($character));
    }

    public function test_can_player_auto_hit_uses_the_chance_calculator_when_chance_is_below_one(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter([], ['name' => 'Thief'])->getCharacter();
        $this->canHitFactory->seedCharacterSheet($character, ['extra_action_chance' => ['has_item' => true, 'chance' => 0.5]]);

        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->with(50.0)->andReturn(true);

        $canHit = $this->canHitFactory->buildCanHit($chanceCalculator);

        $this->assertTrue($canHit->canPlayerAutoHit($character));
    }
}
