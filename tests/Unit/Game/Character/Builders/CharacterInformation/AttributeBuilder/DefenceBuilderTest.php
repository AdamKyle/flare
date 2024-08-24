<?php

namespace Tests\Unit\Game\Character\Builders\CharacterInformation\AttributeBuilder;

use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class DefenceBuilderTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]), 5
        )->givePlayerLocation();
        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterStatBuilder = null;
    }

    public function testBuildDefenceWithNoArmour()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $defence = $this->characterStatBuilder->setCharacter($character)->buildDefence();

        $this->assertEquals($character->ac, $defence);
    }

    public function testBuildDefenceWithArmour()
    {
        $item = $this->createItem([
            'type' => 'armour',
            'name' => 'body',
            'base_ac' => 10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('body', 'body')->getCharacter();

        $defence = $this->characterStatBuilder->setCharacter($character)->buildDefence();

        $this->assertEquals($character->ac + 10, $defence);
    }

    public function testBuildDefenceWithArmourVoided()
    {
        $itemPrefix = $this->createItemAffix([
            'name' => 'Sample',
            'base_ac_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'type' => 'armour',
            'name' => 'body',
            'base_ac' => 10,
            'item_prefix_id' => $itemPrefix->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('body', 'body')->getCharacter();

        $defence = $this->characterStatBuilder->setCharacter($character)->buildDefence(true);

        $this->assertGreaterThan(0, $defence);
    }
}
