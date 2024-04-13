<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders\AttackDetails;

use App\Flare\Models\Character;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\Builders\AttackBuilders\AttackDetails\CharacterAttackBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterAttackBuilderTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix, CreateGameMap, CreateClass, CreateGameSkill, CreateGameClassSpecial;

    private ?CharacterFactory $character;

    private ?CharacterAttackBuilder $characterAttackBuilder;

    public function setUp(): void {
        parent::setUp();

        $this->character              = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->characterAttackBuilder = resolve(CharacterAttackBuilder::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character              = null;
        $this->characterAttackBuilder = null;
    }

    private function setUpCharacterForTests(): Character {
        $item = $this->createItem([
            'type'        => WeaponTypes::STAVE,
            'base_damage' => 10,
        ]);

        $spellDamage = $this->createItem([
            'type'        => SpellTypes::DAMAGE,
            'base_damage' => 10,
        ]);

        return $this->character->inventoryManagement()
                               ->giveItem($item, true, 'left-hand')
                               ->giveItem($spellDamage, true, 'spell-one')
                               ->getCharacter();
    }

    public function testBuildWeaponDamage() {
        $character = $this->setUpCharacterForTests();

        $attack = $this->characterAttackBuilder->setCharacter($character)->buildAttack();

        $this->assertGreaterThan(0, $attack['weapon_damage']);
    }

    public function testBuildCastDamage() {
        $character = $this->setUpCharacterForTests();

        $attack = $this->characterAttackBuilder->setCharacter($character)->buildCastAttack();

        $this->assertGreaterThan(0, $attack['spell_damage']);
    }

    public function testBuildCastAndAttackDamage() {
        $character = $this->setUpCharacterForTests();

        $attack = $this->characterAttackBuilder->setCharacter($character)->buildCastAndAttack();

        $this->assertGreaterThan(0, $attack['spell_damage']);
        $this->assertGreaterThan(0, $attack['weapon_damage']);
        $this->assertEquals(0, $attack['heal_for']);
    }

    public function testBuildAttackAndCastDamage() {
        $character = $this->setUpCharacterForTests();

        $attack = $this->characterAttackBuilder->setCharacter($character)->buildAttackAndCast();

        $this->assertEquals(0, $attack['spell_damage']);
        $this->assertGreaterThan(0, $attack['weapon_damage']);
        $this->assertEquals(0, $attack['heal_for']);
    }

    public function testBuildDefend() {
        $character = $this->setUpCharacterForTests();

        $attack = $this->characterAttackBuilder->setCharacter($character)->buildDefend();

        $this->assertGreaterThan(0, $attack['defence']);
    }

    public function testShouldHaveClassSpecialtyDamageWhenBuildingAttack() {
        $character = $this->setUpCharacterForTests();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id'                            => $character->game_class_id,
            'specialty_damage'                         => 50000,
            'increase_specialty_damage_per_level'      => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id'            => $character->id,
            'game_class_special_id'   => $classSpecial->id,
            'level'                   => 0,
            'current_xp'              => 0,
            'required_xp'             => 100,
            'equipped'                => true,
        ]);

        $character = $character->refresh();

        $attack = $this->characterAttackBuilder->setCharacter($character)->buildAttack();

        $this->assertGreaterThan(0, $attack['weapon_damage']);
        $this->assertNotEmpty($attack['special_damage']);
    }

    public function testShouldNotHaveClassSpecialtyDamageWhenBuildingAttack() {
        $character = $this->setUpCharacterForTests();

        $character = $character->refresh();

        $attack = $this->characterAttackBuilder->setCharacter($character)->buildAttack();

        $this->assertGreaterThan(0, $attack['weapon_damage']);
        $this->assertEmpty($attack['special_damage']);
    }
}
