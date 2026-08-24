<?php

namespace Tests\Unit\Game\Battle\ServerFight\Monster;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Battle\ServerFight\MonsterSpecialAttackFactory;
use Tests\TestCase;

class MonsterSpecialAttackTest extends TestCase
{
    use RefreshDatabase;

    private MonsterSpecialAttackFactory $monsterSpecialAttackFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monsterSpecialAttackFactory = new MonsterSpecialAttackFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->monsterSpecialAttackFactory);
    }

    public function test_physical_attack_is_blocked_when_ac_beats_the_damage(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(0, 1000, 1000);

        $this->assertContains([
            'message' => 'You manage to block the enemies special attack!',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(1000, $special->getCharacterHealth());
    }

    public function test_physical_attack_deals_damage_when_not_blocked(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(0, 1000, 0);

        $this->assertContains([
            'message' => 'You take: 150 damage from the enemies special attack (Physical)!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(850, $special->getCharacterHealth());
    }

    public function test_magical_ice_attack_is_blocked_when_ac_beats_the_damage(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(1, 1000, 1000);

        $this->assertContains([
            'message' => 'You manage to block the enemies special attack!',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(1000, $special->getCharacterHealth());
    }

    public function test_magical_ice_attack_deals_damage_when_not_blocked(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(1, 1000, 0);

        $this->assertContains([
            'message' => 'You take: 200 damage from the enemies special attack (Magical)!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(800, $special->getCharacterHealth());
    }

    public function test_delusional_memories_attack_is_blocked_when_ac_beats_the_damage(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(2, 1000, 1000);

        $this->assertContains([
            'message' => 'You manage to block the enemies special attack!',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(1000, $special->getCharacterHealth());
    }

    public function test_delusional_memories_attack_deals_damage_when_not_blocked(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(2, 1000, 0);

        $this->assertContains([
            'message' => 'You take: 250 damage from the enemies special attack (Delusional)!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(750, $special->getCharacterHealth());
    }

    public function test_banshee_scream_attack_is_blocked_when_ac_beats_the_damage(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(3, 1000, 1000);

        $this->assertContains([
            'message' => 'You manage to block the enemies special attack!',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(1000, $special->getCharacterHealth());
    }

    public function test_banshee_scream_attack_deals_damage_when_not_blocked(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(3, 1000, 0);

        $this->assertContains([
            'message' => 'You take: 220 damage from the enemies special attack (Banshee scream)!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(780, $special->getCharacterHealth());
    }

    public function test_enraged_hate_attack_is_blocked_when_ac_beats_the_damage(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(4, 1000, 1000);

        $this->assertContains([
            'message' => 'You manage to block the enemies special attack!',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(1000, $special->getCharacterHealth());
    }

    public function test_enraged_hate_attack_deals_damage_when_not_blocked(): void
    {
        $special = $this->monsterSpecialAttackFactory->buildMonsterSpecialAttack();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);

        $special->doSpecialAttack(4, 1000, 0);

        $this->assertContains([
            'message' => 'You take: 150 damage from the enemies special attack (Enraged Hate)!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(850, $special->getCharacterHealth());
    }
}
