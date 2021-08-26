<?php

namespace Tests\Unit\Flare\Traits;


use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Traits\ClassBasedBonuses;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class ClassBasedBonusesTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateGameSkill, CreateClass;

    public function setUp(): void {
        parent::setUp();
    }

    public function testGetProphetHealingBonus() {
        $character = $this->createCharacter('Prophet', $this->createItem([
            'type' => 'spell-healing',
        ]), 'spell-one');

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $healingBonus = $trait->prophetHealingBonus($character);

        $this->assertTrue($healingBonus > 0.0);
    }

    public function testGetProphetDamageBonus() {
        $character = $this->createCharacter('Prophet', $this->createItem([
            'type' => 'spell-healing',
        ]), 'spell-one');

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $damageBonus = $trait->prophetDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetHereticDamageBonus() {
        $character = $this->createCharacter('Heretic', $this->createItem([
            'type' => 'spell-damage',
        ]), 'spell-one');

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $damageBonus = $trait->hereticSpellDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetFighterDefenceBonus() {
        $character = $this->createCharacter('Fighter', $this->createItem([
            'type' => 'shield',
        ]), 'left-hand');

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $defenceBonus = $trait->getFightersDefence($character);

        $this->assertTrue($defenceBonus > 0.0);
    }

    public function testGetThievesDamageBonus() {
        $character = $this->createCharacter('Thief', $this->createItem([
            'type' => 'weapon',
        ]), 'left-hand', 2);

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $damageBonus = $trait->getThievesDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetThievesFightTimeOutModBonus() {
        $character = $this->createCharacter('Thief', $this->createItem([
            'type' => 'weapon',
        ]), 'left-hand', 2);

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $fightTimeOutBonus = $trait->getThievesFightTimeout($character);

        $this->assertTrue($fightTimeOutBonus > 0.0);
    }

    public function testGetRangersDamageBonus() {
        $character = $this->createCharacter('Ranger', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $damageBonus = $trait->getRangersDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetRangersFightTimeOutModBonus() {
        $character = $this->createCharacter('Ranger', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $fightTimeOutBonus = $trait->getRangersFightTimeout($character);

        $this->assertTrue($fightTimeOutBonus > 0.0);
    }

    public function testGetVampireDamageBonus() {
        $character = $this->createCharacter('Vampire', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $damageBonus = $trait->getVampiresDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetVampireBaseHealingBonus() {
        $character = $this->createCharacter('Vampire', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');

        $trait = $this->getObjectForTrait(ClassBasedBonuses::class);

        $healingBonus = $trait->getVampiresHealingBonus($character);

        $this->assertTrue($healingBonus > 0.0);
    }

    protected function createCharacter(string $className, Item $item, string $position, int $amount = 1): Character {
        $gameClass = $this->createClass(['name' => $className]);

        $skill = $this->createGameSkill([
            'game_class_id' => $gameClass->id,
            'base_healing_mod_bonus_per_level' => 0.1,
            'base_damage_mod_bonus_per_level' => 0.1,
            'base_ac_mod_bonus_per_level' => 0.1,
            'fight_time_out_mod_bonus_per_level' => 0.1,
        ]);

        return (new CharacterFactory())->createBaseCharacter([], $gameClass)
            ->assignSkill($skill, 25, false)
            ->inventoryManagement()
            ->giveItemMultipleTimes($item, $amount, true, $position)
            ->getCharacter();
    }
}
