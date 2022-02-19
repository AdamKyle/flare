<?php

namespace Tests\Unit\Flare\Builders\CharacterDetails\ClassDetails;


use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class ClassBonusesTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateGameSkill, CreateClass;

    private ?ClassBonuses $classBonuses;

    public function setUp(): void {
        parent::setUp();

        $this->classBonuses = resolve(ClassBonuses::class);
    }

    public function tearDown(): void{
        parent::tearDown();

        $this->classBonuses = null;
    }

    public function testGetProphetHealingBonus() {
        $character = $this->createCharacter('Prophet', $this->createItem([
            'type' => 'spell-healing',
        ]), 'spell-one');

        $healingBonus = $this->classBonuses->prophetHealingBonus($character);

        $this->assertTrue($healingBonus > 0.0);
    }

    public function testGetProphetHasDamageSpells() {
        $character = $this->createCharacter('Prophet', $this->createItem([
            'type' => 'spell-damage',
        ]), 'spell-one');

        $hasDamageSpells = $this->classBonuses->prophetHasDamageSpells($character);

        $this->assertTrue($hasDamageSpells);
    }

    public function testGetHereticDamageBonus() {
        $character = $this->createCharacter('Heretic', $this->createItem([
            'type' => 'spell-damage',
        ]), 'spell-one');


        $damageBonus = $this->classBonuses->hereticSpellDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetHereticDamageBonusWithNoEquipment() {
        $character = $this->createCharacter('Heretic', $this->createItem([
            'type' => 'spell-damage',
        ]), 'spell-one');

        $character->inventory->slots()->delete();

        $damageBonus = $this->classBonuses->hereticSpellDamageBonus($character);

        $this->assertEquals($damageBonus, 0.0);
    }

    public function testGetFighterDamageBonus() {
        $character = $this->createCharacter('Fighter', $this->createItem([
            'type' => 'weapon',
        ]), 'left-hand');

        $damageBonus = $this->classBonuses->getFightersDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetFighterDamageBonusNoEquipment() {
        $character = $this->createCharacter('Fighter', $this->createItem([
            'type' => 'weapon',
        ]), 'left-hand');

        $character->inventory->slots()->delete();

        $damageBonus = $this->classBonuses->getFightersDamageBonus($character);

        $this->assertEquals($damageBonus, 0.0);
    }

    public function testGetFighterDefenceBonus() {
        $character = $this->createCharacter('Fighter', $this->createItem([
            'type' => 'shield',
        ]), 'left-hand');

        $defenceBonus = $this->classBonuses->getFightersDefence($character);

        $this->assertTrue($defenceBonus > 0.0);
    }

    public function testGetThievesDamageBonus() {
        $character = $this->createCharacter('Thief', $this->createItem([
            'type' => 'weapon',
        ]), 'left-hand', 2);

        $damageBonus = $this->classBonuses->getThievesDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetThievesDamageBonusNoEquipment() {
        $character = $this->createCharacter('Thief', $this->createItem([
            'type' => 'weapon',
        ]), 'left-hand', 2);

        $character->inventory->slots()->delete();

        $damageBonus = $this->classBonuses->getThievesDamageBonus($character);

        $this->assertEquals($damageBonus, 0.0);
    }

    public function testGetThievesFightTimeOutModBonus() {
        $character = $this->createCharacter('Thief', $this->createItem([
            'type' => 'weapon',
        ]), 'left-hand', 2);


        $fightTimeOutBonus = $this->classBonuses->getThievesFightTimeout($character);

        $this->assertTrue($fightTimeOutBonus > 0.0);
    }

    public function testGetThievesFightTimeOutModBonusNoEquipment() {
        $character = $this->createCharacter('Thief', $this->createItem([
            'type' => 'weapon',
        ]), 'left-hand', 2);

        $character->inventory->slots()->delete();

        $fightTimeOutBonus = $this->classBonuses->getThievesFightTimeout($character);

        $this->assertEquals($fightTimeOutBonus, 0.0);
    }

    public function testGetRangersDamageBonus() {
        $character = $this->createCharacter('Ranger', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');

        $damageBonus = $this->classBonuses->getRangersDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetRangersDamageBonusNoEquipment() {
        $character = $this->createCharacter('Ranger', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');

        $character->inventory->slots()->delete();

        $damageBonus = $this->classBonuses->getRangersDamageBonus($character);

        $this->assertEquals($damageBonus, 0.0);
    }

    public function testGetRangersFightTimeOutModBonus() {
        $character = $this->createCharacter('Ranger', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');


        $fightTimeOutBonus = $this->classBonuses->getRangersFightTimeout($character);

        $this->assertTrue($fightTimeOutBonus > 0.0);
    }

    public function testGetRangersFightTimeOutModBonusNoEquipment() {
        $character = $this->createCharacter('Ranger', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');

        $character->inventory->slots()->delete();

        $fightTimeOutBonus = $this->classBonuses->getRangersFightTimeout($character);

        $this->assertEquals($fightTimeOutBonus, 0.0);
    }

    public function testGetVampireDamageBonus() {
        $character = $this->createCharacter('Vampire', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');

        $damageBonus = $this->classBonuses->getVampiresDamageBonus($character);

        $this->assertTrue($damageBonus > 0.0);
    }

    public function testGetVampireBaseHealingBonus() {
        $character = $this->createCharacter('Vampire', $this->createItem([
            'type' => 'bow',
        ]), 'left-hand');


        $healingBonus = $this->classBonuses->getVampiresHealingBonus($character);

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
            ->givePlayerLocation()
            ->assignSkill($skill, 25, false)
            ->inventoryManagement()
            ->giveItemMultipleTimes($item, $amount, true, $position)
            ->getCharacter(false);
    }
}
