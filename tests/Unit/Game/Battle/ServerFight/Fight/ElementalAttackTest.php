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

    public function test_no_attack_happens_when_the_defender_has_no_elements_and_not_a_monster_attack(): void
    {
        $elementalAttack = $this->elementalAttackFactory->buildElementalAttack();
        $elementalAttack->setCharacterHealth(100);
        $elementalAttack->setMonsterHealth(100);

        $elementalAttack->doElementalAttack(['fire' => 0, 'ice' => 0, 'water' => 0], ['fire' => 0.5, 'ice' => 0, 'water' => 0], 100, false);

        $this->assertSame(100, $elementalAttack->getMonsterHealth());
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

        $this->assertSame(75, $elementalAttack->getMonsterHealth());
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

        $this->assertSame(0, $elementalAttack->getMonsterHealth());
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

        $this->assertSame(75, $elementalAttack->getCharacterHealth());
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

        $this->assertSame(0, $elementalAttack->getCharacterHealth());
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
