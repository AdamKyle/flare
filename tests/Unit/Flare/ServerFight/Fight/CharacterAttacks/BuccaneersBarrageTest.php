<?php

namespace Tests\Unit\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BuccaneersBarrage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BuccaneersBarrageTest extends TestCase
{
    use RefreshDatabase;

    public function test_special_does_not_fire_when_required_items_missing(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => [
                'has_item' => false,
                'chance' => 1.0,
            ],
        ]);

        $barrage = resolve(BuccaneersBarrage::class);
        $barrage->setCharacterHealth(1000);
        $barrage->setMonsterHealth(1000);
        $barrage->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertEquals(1000, $barrage->getMonsterHealth());
        $this->assertEmpty($barrage->getMessages());
    }

    public function test_special_does_not_fire_when_chance_roll_fails(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => [
                'has_item' => true,
                'chance' => 0.0,
            ],
        ]);

        $barrage = resolve(BuccaneersBarrage::class);
        $barrage->setCharacterHealth(1000);
        $barrage->setMonsterHealth(1000);
        $barrage->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertEquals(1000, $barrage->getMonsterHealth());
    }

    public function test_gun_and_shield_special_deals_three_hits(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => [
                'has_item' => true,
                'chance' => 1.0,
            ],
        ]);

        $barrage = resolve(BuccaneersBarrage::class);
        $barrage->setCharacterHealth(1000);
        $barrage->setMonsterHealth(10000);
        $barrage->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $playerActionMessages = array_filter($barrage->getMessages(), fn ($msg) => $msg['type'] === 'player-action');
        $this->assertCount(3, $playerActionMessages);
    }

    public function test_three_hit_damage_percentages_are25_and15_and5_of_weapon_damage(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => [
                'has_item' => true,
                'chance' => 1.0,
            ],
        ]);

        $barrage = resolve(BuccaneersBarrage::class);
        $barrage->setCharacterHealth(1000);
        $barrage->setMonsterHealth(10000);
        $barrage->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $messages = $barrage->getMessages();
        $playerMessages = array_values(array_filter($messages, fn ($msg) => $msg['type'] === 'player-action'));

        $this->assertStringContainsString('250', $playerMessages[0]['message']);
        $this->assertStringContainsString('150', $playerMessages[1]['message']);
        $this->assertStringContainsString('50', $playerMessages[2]['message']);
    }

    public function test_total_damage_equals_forty_five_percent_of_weapon_damage_with_no_deduction(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => [
                'has_item' => true,
                'chance' => 1.0,
            ],
        ]);

        $barrage = resolve(BuccaneersBarrage::class);
        $barrage->setCharacterHealth(1000);
        $barrage->setMonsterHealth(10000);
        $barrage->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertEquals(10000 - 450, $barrage->getMonsterHealth());
    }

    public function test_damage_deduction_reduces_each_shot(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => [
                'has_item' => true,
                'chance' => 1.0,
            ],
        ]);

        $barrage = resolve(BuccaneersBarrage::class);
        $barrage->setCharacterHealth(1000);
        $barrage->setMonsterHealth(10000);
        $barrage->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.5]);

        $shot1 = (int) (1000 * 0.25 - 1000 * 0.25 * 0.5);
        $shot2 = (int) (1000 * 0.15 - 1000 * 0.15 * 0.5);
        $shot3 = (int) (1000 * 0.05 - 1000 * 0.05 * 0.5);

        $this->assertEquals(10000 - ($shot1 + $shot2 + $shot3), $barrage->getMonsterHealth());

        $deductionMessages = array_filter($barrage->getMessages(), fn ($msg) => $msg['type'] === 'enemy-action');
        $this->assertCount(3, $deductionMessages);
    }

    public function test_raid_boss_cap_applies_per_shot(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => [
                'has_item' => true,
                'chance' => 1.0,
            ],
        ]);

        $hugeDamage = BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 25;

        $barrage = resolve(BuccaneersBarrage::class);
        $barrage->setIsRaidBoss(true);
        $barrage->setCharacterHealth(1000);
        $monsterHealth = (int) (BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $barrage->setMonsterHealth($monsterHealth);
        $barrage->handleAttack($character, ['weapon_damage' => $hugeDamage, 'damage_deduction' => 0.0]);

        $expectedTotalDamage = BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 3;
        $this->assertEquals($monsterHealth - $expectedTotalDamage, $barrage->getMonsterHealth());
    }
}
