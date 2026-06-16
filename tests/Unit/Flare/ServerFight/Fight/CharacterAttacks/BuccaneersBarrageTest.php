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

    public function testSpecialDoesNotFireWhenRequiredItemsMissing(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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

    public function testSpecialDoesNotFireWhenChanceRollFails(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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

    public function testGunAndShieldSpecialDealsThreeHits(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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

        $playerActionMessages = array_filter($barrage->getMessages(), fn($msg) => $msg['type'] === 'player-action');
        $this->assertCount(3, $playerActionMessages);
    }

    public function testThreeHitDamagePercentagesAre25And15And5OfWeaponDamage(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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
        $playerMessages = array_values(array_filter($messages, fn($msg) => $msg['type'] === 'player-action'));

        $this->assertStringContainsString('250', $playerMessages[0]['message']);
        $this->assertStringContainsString('150', $playerMessages[1]['message']);
        $this->assertStringContainsString('50', $playerMessages[2]['message']);
    }

    public function testTotalDamageEqualsFortyFivePercentOfWeaponDamageWithNoDeduction(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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

    public function testDamageDeductionReducesEachShot(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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

        $deductionMessages = array_filter($barrage->getMessages(), fn($msg) => $msg['type'] === 'enemy-action');
        $this->assertCount(3, $deductionMessages);
    }

    public function testRaidBossCapAppliesPerShot(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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
