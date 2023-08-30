<?php

namespace Tests\Unit\Flare\Builders\CharacterInformation\AttributeBuilder;

use App\Flare\Builders\CharacterInformation\AttributeBuilders\DamageBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\ElementalAtonement;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Setup\Character\CharacterFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Values\CharacterClassValue;
use Tests\Traits\CreateGem;

class DamageBuilderTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateClass, CreateGameSkill, CreateGem;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?DamageBuilder $damageBuilder;

    public function setUp(): void {
        parent::setUp();

        $this->character            = (new CharacterFactory())->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);

        $this->damageBuilder = resolve(DamageBuilder::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character            = null;
        $this->characterStatBuilder = null;
        $this->damageBuilder   = null;
    }

    public function testArcaneAlchemistDoesMoreDamageWithStave() {
        $arcaneAlchemist = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::ARCANE_ALCHEMIST,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'stave', 'base_damage' => 100]),
                true,
                'left-hand'
            )
            ->getCharacter();

        $prophet = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::PROPHET,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'stave', 'base_damage' => 100]),
                true,
                'left-hand'
            )
            ->getCharacter();



        $prophetEquipped = $this->characterStatBuilder->fetchEquipped($prophet);

        $this->damageBuilder->initialize($prophet, $prophet->skills, $prophetEquipped);

        $prophetDamage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($prophet)->statMod('chr'));

        $arcaneEquipped = $this->characterStatBuilder->fetchEquipped($arcaneAlchemist);

        $this->damageBuilder->initialize($arcaneAlchemist, $arcaneAlchemist->skills, $arcaneEquipped);

        $arcaneDamage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($arcaneAlchemist)->statMod('chr'));

        $this->assertGreaterThan($prophetDamage, $arcaneDamage);
    }
}
