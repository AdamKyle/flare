<?php

namespace Tests\Unit\Game\Character\Builders\CharacterInformation\AttributeBuilder;

use App\Flare\Values\CharacterClassValue;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class DamageBuilderTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateGem, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?DamageBuilder $damageBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]),
            5
        )->givePlayerLocation();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);

        $this->damageBuilder = resolve(DamageBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterStatBuilder = null;
        $this->damageBuilder = null;
    }

    public function test_arcane_alchemist_does_more_damage_with_stave()
    {
        $arcaneAlchemist = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::ARCANE_ALCHEMIST,
            'damage_stat' => 'str',
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
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

        $prophet = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::PROPHET,
            'damage_stat' => 'str',
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
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
