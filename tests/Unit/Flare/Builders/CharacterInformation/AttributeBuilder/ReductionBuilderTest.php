<?php

namespace Tests\Unit\Flare\Builders\CharacterInformation\AttributeBuilder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ReductionBuilderTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix, CreateGameMap, CreateClass, CreateGameSkill;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    public function setUp(): void {
        parent::setUp();

        $this->character            = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->characterStatBuilder = null;
    }

    public function testNoRingReductionWithEmptyInventory() {

        $character = $this->character->getCharacter();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getRingReduction('spell_evasion');

        $this->assertEquals(0, $reduction);
    }

    public function testGetRingReduction() {

        $item = $this->createItem([
            'type' => 'ring',
            'spell_evasion' => 0.10
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'ring-one')->getCharacter();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getRingReduction('spell_evasion');

        $this->assertEquals(0.10, $reduction);
    }

    public function testGetAffixReductionNoInventory() {

        $character = $this->character->getCharacter();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getAffixReduction('str_reduction');

        $this->assertEquals(0, $reduction);
    }

    public function testGetAffixReduction() {

        $itemAffix = $this->createItemAffix([
           'str_reduction' => 0.10
        ]);

        $item = $this->createItem([
            'type'           => 'ring',
            'spell_evasion'  => 0.10,
            'item_prefix_id' => $itemAffix->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'ring-one')->getCharacter();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getAffixReduction('str_reduction');

        $this->assertEquals(0.10, $reduction);
    }
}
