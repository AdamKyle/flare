<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class GunslingersAssassinationTest extends TestCase
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

    public function test_does_not_fire_when_the_character_has_no_item(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Gunslinger', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0, 'amount' => 1],
        ]);

        $special = $this->specialAttackFactory->buildGunslingersAssassination();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertSame(500, $special->getCharacterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Gunslinger', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0, 'amount' => 1],
        ]);

        $special = $this->specialAttackFactory->buildGunslingersAssassination();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_rapid_fire_deals_forty_percent_damage_with_deduction_and_raid_cap_and_heals(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Gunslinger', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 2],
            'health' => 1000,
        ]);

        $special = $this->specialAttackFactory->buildGunslingersAssassination();
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['weapon_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'you fire off your guns in rapid succession praying you kill the enemy or at least hit it!',
            'type' => 'regular',
        ], $special->getMessages());
        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES,
            $special->getMonsterHealth()
        );
        $this->assertSame(1000, $special->getCharacterHealth());
    }

    public function test_single_shot_deals_sixty_percent_damage_without_deduction(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Gunslinger', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 1],
            'health' => 1000,
        ]);

        $special = $this->specialAttackFactory->buildGunslingersAssassination();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertContains([
            'message' => 'You take careful aim at the enemy and fire a single shot!',
            'type' => 'regular',
        ], $special->getMessages());
        $this->assertContains([
            'message' => 'You hit for (Gunslingers Assassination!) 60',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(10000 - 60, $special->getMonsterHealth());
        $this->assertSame(560, $special->getCharacterHealth());
    }

    public function test_single_shot_applies_deduction(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Gunslinger', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 1],
            'health' => 1000,
        ]);

        $special = $this->specialAttackFactory->buildGunslingersAssassination();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(10000 - 30, $special->getMonsterHealth());
    }
}
