<?php

namespace Tests\Unit\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\ServerFight\BattleBase;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DevilsPiercingShot;
use App\Flare\Values\ClassAttackValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class DevilsPiercingShotTest extends TestCase
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
                'type' => ClassAttackValue::DEVILS_PIERCING_SHOT,
            ],
        ]);

        $special = resolve(DevilsPiercingShot::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertEquals(1000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function testSpecialDoesNotFireWhenCachedTypeIsNotDevilsPiercingShot(): void
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

        $special = resolve(DevilsPiercingShot::class);
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
                'type' => ClassAttackValue::DEVILS_PIERCING_SHOT,
            ],
        ]);

        $special = resolve(DevilsPiercingShot::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertEquals(1000, $special->getMonsterHealth());
    }

    public function testDevilsPiercingShotDealsMainHitPlusFourBleeds(): void
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

        $special = resolve(DevilsPiercingShot::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $playerActionMessages = array_filter($special->getMessages(), fn($msg) => $msg['type'] === 'player-action');
        $this->assertCount(5, $playerActionMessages);
    }

    public function testMainHitIsDoubleWeaponDamage(): void
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

        $special = resolve(DevilsPiercingShot::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 500, 'damage_deduction' => 0.0]);

        $messages = $special->getMessages();
        $playerMessages = array_values(array_filter($messages, fn($msg) => $msg['type'] === 'player-action'));

        $this->assertStringContainsString('1,000', $playerMessages[0]['message']);
    }

    public function testBleedHitsUseCurrentMonsterHealth(): void
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

        $monsterHealth = 10000;
        $weaponDamage = 100;

        $special = resolve(DevilsPiercingShot::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth($monsterHealth);
        $special->handleAttack($character, ['weapon_damage' => $weaponDamage, 'damage_deduction' => 0.0]);

        $afterMainHit = $monsterHealth - $weaponDamage * 2;
        $bleed1 = (int) ($afterMainHit * 0.17);
        $afterBleed1 = $afterMainHit - $bleed1;
        $bleed2 = (int) ($afterBleed1 * 0.14);
        $afterBleed2 = $afterBleed1 - $bleed2;
        $bleed3 = (int) ($afterBleed2 * 0.08);
        $afterBleed3 = $afterBleed2 - $bleed3;
        $bleed4 = (int) ($afterBleed3 * 0.04);
        $expected = $afterBleed3 - $bleed4;

        $this->assertEquals($expected, $special->getMonsterHealth());
    }

    public function testDamageDeductionReducesMainHit(): void
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

        $special = resolve(DevilsPiercingShot::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.5]);

        $deductionMessages = array_filter($special->getMessages(), fn($msg) => $msg['type'] === 'enemy-action');
        $this->assertCount(1, $deductionMessages);
    }

    public function testRaidBossCapAppliesPerMainHit(): void
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

        $hugeDamage = BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 25;
        $monsterHealth = (int) (BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 1000);

        $special = resolve(DevilsPiercingShot::class);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth($monsterHealth);
        $special->handleAttack($character, ['weapon_damage' => $hugeDamage, 'damage_deduction' => 0.0]);

        $messages = $special->getMessages();
        $playerMessages = array_values(array_filter($messages, fn($msg) => $msg['type'] === 'player-action'));
        $this->assertStringContainsString(number_format(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES), $playerMessages[0]['message']);
    }

    public function testBleedHitsDoNotApplyDamageDeduction(): void
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

        $special = resolve(DevilsPiercingShot::class);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.5]);

        $deductionMessages = array_filter($special->getMessages(), fn($msg) => $msg['type'] === 'enemy-action');
        $this->assertCount(1, $deductionMessages);
    }

    public function testEachBleedHitAppliesRaidBossCapIndependently(): void
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

        $hugeDamage = BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 25;
        $monsterHealth = (int) (BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 1000);

        $special = resolve(DevilsPiercingShot::class);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth($monsterHealth);
        $special->handleAttack($character, ['weapon_damage' => $hugeDamage, 'damage_deduction' => 0.0]);

        $messages = $special->getMessages();
        $playerMessages = array_values(array_filter($messages, fn($msg) => $msg['type'] === 'player-action'));

        $cappedValue = number_format(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES);
        $this->assertStringContainsString($cappedValue, $playerMessages[1]['message']);
        $this->assertStringContainsString($cappedValue, $playerMessages[2]['message']);
        $this->assertStringContainsString($cappedValue, $playerMessages[3]['message']);
        $this->assertStringContainsString($cappedValue, $playerMessages[4]['message']);
    }

    public function testTotalMonsterHealthAfterRaidCappedBleedsIsCorrect(): void
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

        $hugeDamage = BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 25;
        $monsterHealth = (int) (BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 1000);

        $special = resolve(DevilsPiercingShot::class);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth($monsterHealth);
        $special->handleAttack($character, ['weapon_damage' => $hugeDamage, 'damage_deduction' => 0.0]);

        $expected = $monsterHealth - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 5;

        $this->assertEquals($expected, $special->getMonsterHealth());
    }
}
