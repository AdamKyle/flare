<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Battle\ServerFight\ElementalAttackFactory;
use Tests\TestCase;

class ElementalAttackTest extends TestCase
{
    use RefreshDatabase;

    private ElementalAttackFactory $elementalAttackFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->elementalAttackFactory = new ElementalAttackFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->elementalAttackFactory);
    }

    public function test_no_attack_happens_when_attacker_has_no_elements(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(['fire' => 0.5, 'ice' => 0, 'water' => 0], [], 100);

        $this->assertSame(100, $elementalAttack->getMonsterHealth());
        $this->assertEmpty($elementalAttack->getMessages());
    }

    public function test_no_attack_happens_when_the_highest_attacking_element_is_zero(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(['fire' => 0.5, 'ice' => 0, 'water' => 0], ['fire' => 0, 'ice' => 0, 'water' => 0], 100);

        $this->assertSame(100, $elementalAttack->getMonsterHealth());
    }

    public function test_player_elemental_attack_applies_when_defender_has_no_resistance(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(['fire' => 0, 'ice' => 0, 'water' => 0], ['fire' => 0.5, 'ice' => 0, 'water' => 0], 100, false);

        $this->assertSame(50, $elementalAttack->getMonsterHealth());
    }

    public function test_regular_damage_is_dealt_when_the_defender_has_no_elements(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack([], ['fire' => 0.5, 'ice' => 0, 'water' => 0], 100, true);

        $this->assertSame(50, $elementalAttack->getCharacterHealth());
    }

    public function test_half_damage_is_dealt_when_the_attacking_element_is_weak_against_the_defender(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.5, 'ice' => 0, 'water' => 0],
            ['water' => 1.0, 'fire' => 0, 'ice' => 0],
            100,
            false
        );

        $this->assertSame(50, $elementalAttack->getMonsterHealth());
    }

    public function test_double_damage_is_dealt_when_the_attacking_element_is_strong_against_the_defender(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.5, 'ice' => 0, 'water' => 0],
            ['ice' => 1.0, 'fire' => 0, 'water' => 0],
            100,
            false
        );

        $this->assertSame(-100, $elementalAttack->getMonsterHealth());
    }

    public function test_regular_damage_is_dealt_when_the_elements_are_the_same(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.5, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            false
        );

        $this->assertSame(50, $elementalAttack->getMonsterHealth());
    }

    public function test_monster_regular_attack_reduces_the_character_health(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.5, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            true
        );

        $this->assertSame(50, $elementalAttack->getCharacterHealth());
    }

    public function test_monster_half_damage_attack_reduces_the_character_health(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.5, 'ice' => 0, 'water' => 0],
            ['water' => 1.0, 'fire' => 0, 'ice' => 0],
            100,
            true
        );

        $this->assertSame(50, $elementalAttack->getCharacterHealth());
    }

    public function test_monster_double_damage_attack_reduces_the_character_health(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.5, 'ice' => 0, 'water' => 0],
            ['ice' => 1.0, 'fire' => 0, 'water' => 0],
            100,
            true
        );

        $this->assertSame(-100, $elementalAttack->getCharacterHealth());
    }

    public function test_unrelated_high_resistance_does_not_suppress_player_attack(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0, 'ice' => 0.75, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            false,
        );

        $this->assertSame(50, $elementalAttack->getMonsterHealth());
    }

    public function test_player_penetration_subtracts_matching_resistance_without_going_below_zero(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.25, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            false,
            ['fire' => 0.50, 'ice' => 0, 'water' => 0],
        );

        $this->assertSame(0, $elementalAttack->getMonsterHealth());
    }

    public function test_character_penetration_reduces_matching_monster_atonement_to_the_effective_resistance(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.75, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            false,
            ['fire' => 0.20, 'ice' => 0, 'water' => 0],
        );

        $this->assertSame(55, $elementalAttack->getMonsterHealth());
    }

    public function test_monster_penetration_reduces_matching_character_atonement_to_the_effective_resistance(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.75, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            true,
            ['fire' => 0.20, 'ice' => 0, 'water' => 0],
        );

        $this->assertSame(55, $elementalAttack->getCharacterHealth());
    }

    public function test_monster_penetration_cannot_reduce_character_resistance_below_zero(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.10, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            true,
            ['fire' => 0.25, 'ice' => 0, 'water' => 0],
        );

        $this->assertSame(0, $elementalAttack->getCharacterHealth());
    }

    public function test_monster_penetration_for_another_element_does_not_reduce_the_matching_resistance(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.50, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            true,
            ['fire' => 0, 'ice' => 0.50, 'water' => 0],
        );

        $this->assertSame(50, $elementalAttack->getCharacterHealth());
    }

    public function test_character_penetration_message_reports_monster_atonement_penetration_and_effective_resistance(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.75, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            false,
            ['fire' => 0.20, 'ice' => 0, 'water' => 0],
        );

        $this->assertContains([
            'message' => 'The enemy\'s Fire Atonement of 75.00% is reduced by your 20.00% Fire Penetration, leaving 55.00% effective resistance.',
            'type' => 'enemy-action',
        ], $elementalAttack->getMessages());
    }

    public function test_monster_penetration_message_reports_character_atonement_penetration_and_effective_resistance(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.75, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            true,
            ['fire' => 0.20, 'ice' => 0, 'water' => 0],
        );

        $this->assertContains([
            'message' => 'Your Fire Atonement of 75.00% is reduced by the enemy\'s 20.00% Fire Penetration, leaving 55.00% effective resistance.',
            'type' => 'regular',
        ], $elementalAttack->getMessages());
    }

    public function test_penetration_message_reports_the_attacking_element_rather_than_fire(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0, 'ice' => 0.60, 'water' => 0],
            ['fire' => 0, 'ice' => 1.0, 'water' => 0],
            100,
            true,
            ['fire' => 0.30, 'ice' => 0.15, 'water' => 0],
        );

        $this->assertContains([
            'message' => 'Your Ice Atonement of 60.00% is reduced by the enemy\'s 15.00% Ice Penetration, leaving 45.00% effective resistance.',
            'type' => 'regular',
        ], $elementalAttack->getMessages());
    }

    public function test_zero_penetration_keeps_the_simple_resistance_message(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.50, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            100,
            true,
        );

        $messages = array_column($elementalAttack->getMessages(), 'message');

        $this->assertContains('You resist 50.00% of the enemy\'s Fire Gem damage.', $messages);
        $this->assertEmpty(array_filter($messages, fn (string $message): bool => str_contains($message, 'Penetration')));
    }

    public function test_raid_boss_damage_is_capped_for_non_monster_attacks(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setIsRaidBoss(true);
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(5_000_000_000_000);

        $elementalAttack->doElementalAttack(
            ['fire' => 0.5, 'ice' => 0, 'water' => 0],
            ['fire' => 1.0, 'ice' => 0, 'water' => 0],
            4_000_000_000_000,
            false
        );

        $this->assertSame(4_000_000_000_000, $elementalAttack->getMonsterHealth());
    }
}
