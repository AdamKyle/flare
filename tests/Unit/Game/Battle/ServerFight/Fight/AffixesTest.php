<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight;

use App\Game\Battle\ServerFight\Fight\Affixes;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Combat\Values\AttackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\AffixesFactory;
use Tests\TestCase;

class AffixesTest extends TestCase
{
    use RefreshDatabase;

    private AffixesFactory $affixesFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->affixesFactory = new AffixesFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->affixesFactory);
    }

    public function test_get_character_affix_damage_returns_zero_for_a_defend_attack(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();

        $result = $affixes->getCharacterAffixDamage([
            'attack_type' => AttackType::DEFEND->value,
        ]);

        $this->assertSame(0, $result);
    }

    public function test_get_character_affix_damage_returns_zero_when_no_damage_is_stacked(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();

        $result = $affixes->getCharacterAffixDamage([
            'attack_type' => AttackType::ATTACK->value,
            'weapon_damage' => 100,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 0.0,
                'non_stacking_damage' => 0.0,
                'cant_be_resisted' => false,
            ],
        ]);

        $this->assertSame(0, $result);
    }

    public function test_get_character_affix_damage_returns_full_weapon_damage_when_cant_be_resisted(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();

        $result = $affixes->getCharacterAffixDamage([
            'attack_type' => AttackType::ATTACK->value,
            'weapon_damage' => 100,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 0.5,
                'non_stacking_damage' => 0.5,
                'cant_be_resisted' => true,
            ],
        ]);

        $this->assertSame(150, $result);
    }

    public function test_get_character_affix_damage_caps_weapon_damage_for_raid_bosses(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();
        $affixes->setIsRaidBoss(true);

        $result = $affixes->getCharacterAffixDamage([
            'attack_type' => AttackType::ATTACK->value,
            'weapon_damage' => 4_000_000_000_000,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 1.0,
                'non_stacking_damage' => 1.0,
                'cant_be_resisted' => true,
            ],
        ]);

        $this->assertSame(Affixes::MAX_DAMAGE_FOR_RAID_BOSSES, $result);
    }

    public function test_get_character_affix_damage_uses_spell_damage_when_weapon_damage_is_absent(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();

        $result = $affixes->getCharacterAffixDamage([
            'attack_type' => AttackType::CAST->value,
            'spell_damage' => 100,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 0.5,
                'non_stacking_damage' => 0.5,
                'cant_be_resisted' => true,
            ],
        ]);

        $this->assertSame(150, $result);
    }

    public function test_get_character_affix_damage_returns_non_stacking_damage_message_when_non_stacking_weapon_damage_is_zero_or_less(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();

        $result = $affixes->getCharacterAffixDamage([
            'attack_type' => AttackType::ATTACK->value,
            'weapon_damage' => 100,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 0.5,
                'non_stacking_damage' => -2.0,
                'cant_be_resisted' => false,
            ],
        ]);

        $this->assertSame(150, $result);
    }

    public function test_get_character_affix_damage_resolves_via_affix_damage_when_resistance_makes_it_unresistable(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();

        $result = $affixes->getCharacterAffixDamage([
            'attack_type' => AttackType::ATTACK->value,
            'weapon_damage' => 100,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 0.5,
                'non_stacking_damage' => 0.5,
                'cant_be_resisted' => false,
            ],
        ], 1.0);

        $this->assertSame(200, $result);
    }

    public function test_get_character_affix_damage_resolves_via_affix_damage_when_the_chance_calculator_denies_the_resist(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(false);

        $affixes = $this->affixesFactory->buildAffixes($chanceCalculator);

        $result = $affixes->getCharacterAffixDamage([
            'attack_type' => AttackType::ATTACK->value,
            'weapon_damage' => 100,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 0.5,
                'non_stacking_damage' => 0.5,
                'cant_be_resisted' => false,
            ],
        ], 0.0);

        $this->assertSame(150, $result);
    }

    public function test_get_affix_life_steal_returns_zero_when_monster_health_is_not_positive(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();
        $character = $this->affixesFactory->buildCharacter();

        $result = $affixes->getAffixLifeSteal($character, [
            'affixes' => ['life_stealing' => 0.5, 'cant_be_resisted' => false],
            'damage_deduction' => 0.0,
        ], 0);

        $this->assertSame(0, $result);
    }

    public function test_get_affix_life_steal_returns_zero_when_life_stealing_is_zero_or_less(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();
        $character = $this->affixesFactory->buildCharacter();

        $result = $affixes->getAffixLifeSteal($character, [
            'affixes' => ['life_stealing' => 0.0, 'cant_be_resisted' => false],
            'damage_deduction' => 0.0,
        ], 100);

        $this->assertSame(0, $result);
    }

    public function test_get_affix_life_steal_returns_damage_when_cant_be_resisted_for_non_vampires(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();
        $character = $this->affixesFactory->buildCharacter(['name' => 'Fighter']);

        $result = $affixes->getAffixLifeSteal($character, [
            'affixes' => ['life_stealing' => 0.5, 'cant_be_resisted' => true],
            'damage_deduction' => 0.0,
        ], 100);

        $this->assertSame(50, $result);
    }

    public function test_get_affix_life_steal_uses_stacking_life_stealing_and_caps_for_raid_bosses(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();
        $affixes->setIsRaidBoss(true);
        $character = $this->affixesFactory->buildCharacter(['name' => 'Vampire']);

        $result = $affixes->getAffixLifeSteal($character, [
            'affixes' => ['stacking_life_stealing' => 1.0, 'cant_be_resisted' => true],
            'damage_deduction' => 0.0,
        ], 4_000_000_000_000);

        $this->assertSame(Affixes::MAX_DAMAGE_FOR_RAID_BOSSES, $result);
    }

    public function test_get_affix_life_steal_returns_damage_when_enemy_is_entranced(): void
    {
        $affixes = $this->affixesFactory->buildAffixes();
        $affixes->setEntranced();
        $character = $this->affixesFactory->buildCharacter(['name' => 'Fighter']);

        $result = $affixes->getAffixLifeSteal($character, [
            'affixes' => ['life_stealing' => 0.5, 'cant_be_resisted' => false],
            'damage_deduction' => 0.0,
        ], 100);

        $this->assertSame(50, $result);
    }

    public function test_get_affix_life_steal_returns_damage_when_the_chance_calculator_allows_it(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $affixes = $this->affixesFactory->buildAffixes($chanceCalculator);
        $character = $this->affixesFactory->buildCharacter(['name' => 'Fighter']);

        $result = $affixes->getAffixLifeSteal($character, [
            'affixes' => ['life_stealing' => 0.5, 'cant_be_resisted' => false],
            'damage_deduction' => 0.0,
        ], 100);

        $this->assertSame(50, $result);
    }

    public function test_get_affix_life_steal_returns_zero_when_the_chance_calculator_denies_it(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(false);

        $affixes = $this->affixesFactory->buildAffixes($chanceCalculator);
        $character = $this->affixesFactory->buildCharacter(['name' => 'Fighter']);

        $result = $affixes->getAffixLifeSteal($character, [
            'affixes' => ['life_stealing' => 0.5, 'cant_be_resisted' => false],
            'damage_deduction' => 0.0,
        ], 100);

        $this->assertSame(0, $result);
    }
}
