<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class DoubleHealTest extends TestCase
{
    use RefreshDatabase;

    private SpecialAttackFactory $specialAttackFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->specialAttackFactory = new SpecialAttackFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->specialAttackFactory);
    }

    public function test_returns_zero_when_the_character_has_no_item(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Prophet', ['damage_stat' => 'int', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildDoubleHeal();
        $special->setCharacterHealth(1000);

        $this->assertSame(0, $special->handleHeal($character, ['heal_for' => 100]));
        $this->assertEmpty($special->getMessages());
    }

    public function test_returns_zero_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Prophet', ['damage_stat' => 'int', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildDoubleHeal();
        $special->setCharacterHealth(1000);

        $this->assertSame(0, $special->handleHeal($character, ['heal_for' => 100]));
    }

    public function test_returns_double_heal_on_a_critical_heal(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Prophet', ['damage_stat' => 'int', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
            'skills' => ['criticality' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildDoubleHeal();
        $special->setCharacterHealth(1000);

        $result = $special->handleHeal($character, ['heal_for' => 100]);

        $this->assertSame(200, $result);
        $this->assertContains([
            'message' => 'The heavens open and your wounds start to heal over (Critical heal!)',
            'type' => 'regular',
        ], $special->getMessages());
    }

    public function test_returns_bonus_heal_on_a_normal_heal(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Prophet', ['damage_stat' => 'int', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
            'skills' => ['criticality' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildDoubleHeal();
        $special->setCharacterHealth(1000);

        $result = $special->handleHeal($character, ['heal_for' => 100]);

        $this->assertSame(115, $result);
        $this->assertContains([
            'message' => 'Your healing spell(s) heals for an additional: 115',
            'type' => 'player-action',
        ], $special->getMessages());
    }
}
