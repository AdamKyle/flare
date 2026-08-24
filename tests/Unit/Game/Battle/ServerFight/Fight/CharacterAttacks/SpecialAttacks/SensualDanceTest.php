<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class SensualDanceTest extends TestCase
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
        $character = $this->specialAttackFactory->buildCharacter('Dancer', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0, 'amount' => 1],
        ]);

        $special = $this->specialAttackFactory->buildSensualDance();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Dancer', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0, 'amount' => 1],
        ]);

        $special = $this->specialAttackFactory->buildSensualDance();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_dance_of_death_hits_three_times_with_deduction_and_raid_cap_and_heals(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Dancer', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 2],
            'health' => 1000,
        ]);

        $special = $this->specialAttackFactory->buildSensualDance();
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['weapon_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'You dance around the enemy, enticing it with your body. The dance of love. The dance of death!',
            'type' => 'regular',
        ], $special->getMessages());
        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 3,
            $special->getMonsterHealth()
        );
        $this->assertSame(1000, $special->getCharacterHealth());

        $playerMessages = array_values(array_filter($special->getMessages(), fn ($msg) => $msg['type'] === 'player-action'));
        $this->assertCount(3, $playerMessages);
    }

    public function test_aggressive_dance_hits_nine_times_without_deduction(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Dancer', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 1],
            'health' => 1000,
        ]);

        $special = $this->specialAttackFactory->buildSensualDance();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertContains([
            'message' => 'Your dance becomes more aggressive, the cuts on the enemy are small nicks of devastating wounds!',
            'type' => 'regular',
        ], $special->getMessages());
        $this->assertSame(10000 - 15 * 9, $special->getMonsterHealth());

        $playerMessages = array_values(array_filter($special->getMessages(), fn ($msg) => $msg['type'] === 'player-action'));
        $this->assertCount(9, $playerMessages);
    }

    public function test_aggressive_dance_applies_deduction(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Dancer', ['damage_stat' => 'dex', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0, 'amount' => 1],
            'health' => 1000,
        ]);

        $special = $this->specialAttackFactory->buildSensualDance();
        $special->setCharacterHealth(500);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(10000 - 7 * 9, $special->getMonsterHealth());
    }
}
