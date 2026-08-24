<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class BloodyPukeTest extends TestCase
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
        $character = $this->specialAttackFactory->buildCharacter('Alcoholic', ['damage_stat' => 'dur', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildBloodyPuke();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertSame(1000, $special->getCharacterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Alcoholic', ['damage_stat' => 'dur', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildBloodyPuke();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_puke_deals_damage_and_costs_the_character_health(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Alcoholic', ['damage_stat' => 'dur', 'to_hit_stat' => 'dex']);
        $character->update(['dur' => 1000]);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildBloodyPuke();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['damage_deduction' => 0.0]);

        $this->assertSame(10000 - 300, $special->getMonsterHealth());
        $this->assertSame(1000 - 150, $special->getCharacterHealth());
        $this->assertContains([
            'message' => 'You cannot hold it in, you vomit blood and bile so acidic your enemy cannot handle it! (You dealt: 300)',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertContains([
            'message' => 'You lost a lot of blood in your attack. (You took: 150)',
            'type' => 'enemy-action',
        ], $special->getMessages());
    }

    public function test_puke_applies_deduction_and_raid_cap(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Alcoholic', ['damage_stat' => 'dur', 'to_hit_stat' => 'dex']);
        $character->update(['dur' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100]);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildBloodyPuke();
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage! You will still suffer the 15% damage for vomiting blood.',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES,
            $special->getMonsterHealth()
        );
    }
}
