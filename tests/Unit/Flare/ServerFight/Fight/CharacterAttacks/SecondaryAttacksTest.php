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

    public function testNormalMonsterLifeStealingResistanceReducesDamageHealsAndCapsPlayerHealth(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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

    public function testMonsterWithZeroLifeStealingResistanceDoesNotAddResistanceMessage(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-' . $character->id, [
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
}
