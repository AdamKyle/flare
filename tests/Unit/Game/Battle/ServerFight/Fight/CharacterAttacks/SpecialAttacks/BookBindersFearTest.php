<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class BookBindersFearTest extends TestCase
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
        $character = $this->specialAttackFactory->buildCharacter('Book Binder', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0, 'amount' => 1],
        ]);

        $special = $this->specialAttackFactory->buildBookBindersFear();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Book Binder', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0, 'amount' => 1],
        ]);

        $special = $this->specialAttackFactory->buildBookBindersFear();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_dual_awl_lunge_hits_twice_with_deduction_and_raid_cap_and_heals(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Book Binder', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 2],
            'health' => 1000,
        ]);

        $special = $this->specialAttackFactory->buildBookBindersFear();
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['weapon_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'You lunge at the enemy with both scratch awls and aim for the eyes!',
            'type' => 'regular',
        ], $special->getMessages());
        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 2,
            $special->getMonsterHealth()
        );
        $this->assertSame(1000, $special->getCharacterHealth());
    }

    public function test_rapid_stabbing_hits_twenty_two_times_with_raid_cap(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Book Binder', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 1],
            'health' => 4_000_000_000_000_000,
        ]);

        $special = $this->specialAttackFactory->buildBookBindersFear();
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['weapon_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100, 'damage_deduction' => 0.0]);

        $this->assertContains([
            'message' => 'Your fear beings to mount and you start rapidly stabbing the enemy!',
            'type' => 'regular',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 22,
            $special->getMonsterHealth()
        );

        $playerMessages = array_values(array_filter($special->getMessages(), fn ($msg) => $msg['type'] === 'player-action'));
        $this->assertCount(22, $playerMessages);
    }

    public function test_rapid_stabbing_applies_deduction(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Book Binder', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 1],
            'health' => 1000,
        ]);

        $special = $this->specialAttackFactory->buildBookBindersFear();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(10000 - 11 * 22, $special->getMonsterHealth());
    }
}
