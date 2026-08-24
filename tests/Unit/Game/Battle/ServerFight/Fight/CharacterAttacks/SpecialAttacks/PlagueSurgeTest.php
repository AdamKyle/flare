<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class PlagueSurgeTest extends TestCase
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
        $character = $this->specialAttackFactory->buildCharacter('Apothecary', ['damage_stat' => 'focus', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildPlagueSurge();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Apothecary', ['damage_stat' => 'focus', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildPlagueSurge();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_surge_deals_damage_heals_and_caps_for_raid_bosses(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Apothecary', ['damage_stat' => 'focus', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
            'focus_modded' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10,
            'health' => 4_000_000_000_000_000,
        ]);

        $special = $this->specialAttackFactory->buildPlagueSurge();
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The shadows dance while you unleash the poison and siphon the life of the weak.',
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
    }

    public function test_surge_without_deduction_deals_base_damage_and_caps_healing(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Apothecary', ['damage_stat' => 'focus', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
            'focus_modded' => 100,
            'health' => 600,
        ]);

        $special = $this->specialAttackFactory->buildPlagueSurge();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertContains([
            'message' => 'You hit for (Plague Surge!) (and healed for) 122',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(10000 - 122, $special->getMonsterHealth());
        $this->assertSame(600, $special->getCharacterHealth());
    }
}
