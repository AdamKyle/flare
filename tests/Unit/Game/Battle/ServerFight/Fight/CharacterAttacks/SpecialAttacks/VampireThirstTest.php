<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class VampireThirstTest extends TestCase
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
        $character = $this->specialAttackFactory->buildCharacter('Vampire', ['damage_stat' => 'dur', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildVampireThirst();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertSame(500, $special->getCharacterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Vampire', ['damage_stat' => 'dur', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildVampireThirst();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_thirst_deals_damage_heals_and_caps_at_max_health(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Vampire', ['damage_stat' => 'dur', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
            'dur_modded' => 1000,
            'health' => 900,
        ]);

        $special = $this->specialAttackFactory->buildVampireThirst();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertSame(10000 - 1150, $special->getMonsterHealth());
        $this->assertSame(900, $special->getCharacterHealth());
        $this->assertContains([
            'message' => 'You hit for (thirst!) (and healed for) 1,150',
            'type' => 'player-action',
        ], $special->getMessages());
    }

    public function test_thirst_applies_deduction_and_raid_cap(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Vampire', ['damage_stat' => 'dur', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
            'dur_modded' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10,
            'health' => 4_000_000_000_000_000,
        ]);

        $special = $this->specialAttackFactory->buildVampireThirst();
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES,
            $special->getMonsterHealth()
        );
    }
}
