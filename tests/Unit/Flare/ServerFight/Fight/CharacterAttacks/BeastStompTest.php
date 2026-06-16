<?php

namespace Tests\Unit\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BeastStomp;
use App\Flare\Values\ClassAttackValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BeastStompTest extends TestCase
{
    use RefreshDatabase;

    public function testSpecialDoesNotFireWhenRequiredItemsMissing(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $special = resolve(BeastStomp::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertEquals(1000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function testSpecialDoesNotFireWhenCachedTypeIsNotBeastStomp(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::DEVILS_PIERCING_SHOT,
            ],
        ]);

        $special = resolve(BeastStomp::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertEquals(1000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function testSpecialDoesNotFireWhenChanceRollFails(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $special = resolve(BeastStomp::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertEquals(1000, $special->getMonsterHealth());
    }

    public function testBeastStompDealsMainStompPlusEarthCrust(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $special = resolve(BeastStomp::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $playerActionMessages = array_filter($special->getMessages(), fn($msg) => $msg['type'] === 'player-action');
        $this->assertCount(2, $playerActionMessages);
    }

    public function testMainStompIsDoubleWeaponDamage(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $special = resolve(BeastStomp::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 500, 'damage_deduction' => 0.0]);

        $messages = $special->getMessages();
        $playerMessages = array_values(array_filter($messages, fn($msg) => $msg['type'] === 'player-action'));

        $this->assertStringContainsString('1,000', $playerMessages[0]['message']);
    }

    public function testEarthCrustIsQuarterOfHealthAfterStomp(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $monsterHealth = 10000;
        $weaponDamage = 1000;

        $special = resolve(BeastStomp::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth($monsterHealth);
        $special->handleAttack($character, ['weapon_damage' => $weaponDamage, 'damage_deduction' => 0.0]);

        $afterStomp = $monsterHealth - $weaponDamage * 2;
        $earthCrust = (int) ($afterStomp * 0.25);
        $expected = $afterStomp - $earthCrust;

        $this->assertEquals($expected, $special->getMonsterHealth());
    }

    public function testDamageDeductionReducesStomp(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $special = resolve(BeastStomp::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.5]);

        $deductionMessages = array_filter($special->getMessages(), fn($msg) => $msg['type'] === 'enemy-action');
        $this->assertCount(1, $deductionMessages);
    }

    public function testRaidBossCapAppliesPerStomp(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $hugeDamage = BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 25;
        $monsterHealth = (int) (BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 1000);

        $special = resolve(BeastStomp::class);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth($monsterHealth);
        $special->handleAttack($character, ['weapon_damage' => $hugeDamage, 'damage_deduction' => 0.0]);

        $messages = $special->getMessages();
        $playerMessages = array_values(array_filter($messages, fn($msg) => $msg['type'] === 'player-action'));
        $this->assertStringContainsString(number_format(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES), $playerMessages[0]['message']);
    }

    public function testEarthCrustHitDoesNotApplyDamageDeduction(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $special = resolve(BeastStomp::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.5]);

        $deductionMessages = array_filter($special->getMessages(), fn($msg) => $msg['type'] === 'enemy-action');
        $this->assertCount(1, $deductionMessages);
    }

    public function testEarthCrustHitAppliesRaidBossCap(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $hugeDamage = BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 25;
        $monsterHealth = (int) (BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 1000);

        $special = resolve(BeastStomp::class);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth($monsterHealth);
        $special->handleAttack($character, ['weapon_damage' => $hugeDamage, 'damage_deduction' => 0.0]);

        $messages = $special->getMessages();
        $playerMessages = array_values(array_filter($messages, fn($msg) => $msg['type'] === 'player-action'));

        $this->assertStringContainsString(number_format(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES), $playerMessages[1]['message']);
    }

    public function testTotalMonsterHealthAfterRaidCappedEarthCrustIsCorrect(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Beastmaster',
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
                'type' => ClassAttackValue::BEAST_STOMP,
            ],
        ]);

        $hugeDamage = BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 25;
        $monsterHealth = (int) (BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 1000);

        $special = resolve(BeastStomp::class);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth($monsterHealth);
        $special->handleAttack($character, ['weapon_damage' => $hugeDamage, 'damage_deduction' => 0.0]);

        $expected = $monsterHealth - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 2;

        $this->assertEquals($expected, $special->getMonsterHealth());
    }
}
