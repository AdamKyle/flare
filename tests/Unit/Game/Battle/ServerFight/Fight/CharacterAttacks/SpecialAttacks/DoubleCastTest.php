<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class DoubleCastTest extends TestCase
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
        $character = $this->specialAttackFactory->buildCharacter('Heretic', ['damage_stat' => 'int', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildDoubleCast();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['spell_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Heretic', ['damage_stat' => 'int', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildDoubleCast();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['spell_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_double_cast_deals_bonus_damage_with_deduction_and_raid_cap(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Heretic', ['damage_stat' => 'int', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildDoubleCast();
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['spell_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10, 'damage_deduction' => 0.5]);

        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - (int) (BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 0.5),
            $special->getMonsterHealth()
        );

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
    }
}
