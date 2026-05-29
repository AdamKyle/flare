<?php

namespace Tests\Unit\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\Values\AttackTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class SecondaryAttacksTest extends TestCase
{
    use CreateMonster, RefreshDatabase;

    public function test_normal_monster_life_stealing_resistance_reduces_damage_heals_and_caps_player_health(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'life_stealing_resistance' => 0.5,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = (new ServerMonster)->setHealth(1000)->setMonster($monster->toArray());
        $secondaryAttacks = resolve(SecondaryAttacks::class);
        $secondaryAttacks->setCharacterHealth(900);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackTypeValue::ATTACK,
            'damage_deduction' => 0.0,
            'affixes' => [
                'cant_be_resisted' => true,
                'stacking_life_stealing' => 0.0,
                'life_stealing' => 0.5,
            ],
            'weapon_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->affixLifeStealingDamage($character, $serverMonster);

        $this->assertEquals(750, $secondaryAttacks->getMonsterHealth());
        $this->assertEquals(1000, $secondaryAttacks->getCharacterHealth());
        $this->assertContains([
            'message' => 'The enemy resisted your attempt to steal 50.00% of their health and instead you stole 25.00%, dealing 250 damage.',
            'type' => 'enemy-action',
        ], $secondaryAttacks->getMessages());
    }

    public function test_monster_with_zero_life_stealing_resistance_does_not_add_resistance_message(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'life_stealing_resistance' => 0.0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = (new ServerMonster)->setHealth(1000)->setMonster($monster->toArray());
        $secondaryAttacks = resolve(SecondaryAttacks::class);
        $secondaryAttacks->setCharacterHealth(500);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackTypeValue::ATTACK,
            'damage_deduction' => 0.0,
            'affixes' => [
                'cant_be_resisted' => true,
                'stacking_life_stealing' => 0.0,
                'life_stealing' => 0.5,
            ],
            'weapon_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->affixLifeStealingDamage($character, $serverMonster);

        $this->assertEquals(500, $secondaryAttacks->getMonsterHealth());
        $this->assertEquals(1000, $secondaryAttacks->getCharacterHealth());
        $this->assertNotContains([
            'message' => 'The enemy resisted your attempt to steal 50.00% of their health and instead you stole 25.00%, dealing 250 damage.',
            'type' => 'enemy-action',
        ], $secondaryAttacks->getMessages());
    }

    public function test_player_elemental_damage_does_not_fire_when_monster_has_no_atonement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'elemental_atonement' => [
                'atonements' => [
                    'fire' => 0.5,
                    'ice' => 0,
                    'water' => 0,
                ],
            ],
            'weapon_attack' => 100,
            'spell_attack' => 100,
        ]);

        $monster = $this->createMonster([
            'fire_atonement' => null,
            'ice_atonement' => null,
            'water_atonement' => null,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = (new ServerMonster)->setHealth(1000)->setMonster($monster->toArray());
        $secondaryAttacks = resolve(SecondaryAttacks::class);
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackTypeValue::ATTACK,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
        $this->assertEmpty($secondaryAttacks->getMessages());
    }

    public function test_player_elemental_damage_uses_correct_damage_source_for_attack_types(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'elemental_atonement' => [
                'atonements' => [
                    'fire' => 0.5,
                    'ice' => 0,
                    'water' => 0,
                ],
            ],
            'weapon_attack' => 200,
            'spell_attack' => 100,
        ]);

        $monster = $this->createMonster([
            'fire_atonement' => 0.5,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = (new ServerMonster)->setHealth(1000)->setMonster($monster->toArray());
        $secondaryAttacks = resolve(SecondaryAttacks::class);
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackTypeValue::ATTACK,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 200,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);
        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(950, $secondaryAttacks->getMonsterHealth());

        $secondaryAttacks = resolve(SecondaryAttacks::class);
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackTypeValue::CAST,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'spell_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);
        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(975, $secondaryAttacks->getMonsterHealth());

        $secondaryAttacks = resolve(SecondaryAttacks::class);
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackTypeValue::ATTACK_AND_CAST,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 200,
            'spell_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);
        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(950, $secondaryAttacks->getMonsterHealth());

        $secondaryAttacks = resolve(SecondaryAttacks::class);
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackTypeValue::CAST_AND_ATTACK,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 200,
            'spell_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);
        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(975, $secondaryAttacks->getMonsterHealth());
    }

    public function test_player_elemental_damage_does_not_fire_when_defending(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'elemental_atonement' => [
                'atonements' => [
                    'fire' => 0.5,
                    'ice' => 0,
                    'water' => 0,
                ],
            ],
            'weapon_attack' => 200,
            'spell_attack' => 100,
        ]);

        $monster = $this->createMonster([
            'fire_atonement' => 0.5,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = (new ServerMonster)->setHealth(1000)->setMonster($monster->toArray());
        $secondaryAttacks = resolve(SecondaryAttacks::class);
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackTypeValue::DEFEND,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
        $this->assertEmpty($secondaryAttacks->getMessages());
    }
}
