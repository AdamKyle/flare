<?php

namespace Tests\Unit\Game\Character\Builders\CharacterInformation\AttributeBuilder;

use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ReductionBuilderTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateGameSkill, CreateItem, CreateItemAffix, CreateGameClassSpecial, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterStatBuilder = null;
    }

    public function test_no_ring_reduction_with_empty_inventory()
    {

        $character = $this->character->getCharacter();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getRingReduction('spell_evasion');

        $this->assertEquals(0, $reduction);
    }

    public function test_get_ring_reduction()
    {

        $item = $this->createItem([
            'type' => 'ring',
            'spell_evasion' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'ring-one')->getCharacter();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getRingReduction('spell_evasion');

        $this->assertEquals(0.10, $reduction);
    }

    public function test_get_affix_reduction_no_inventory()
    {

        $character = $this->character->getCharacter();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getAffixReduction('str_reduction');

        $this->assertEquals(0, $reduction);
    }

    public function test_get_affix_reduction()
    {

        $itemAffix = $this->createItemAffix([
            'str_reduction' => 0.10,
        ]);

        $item = $this->createItem([
            'type' => 'ring',
            'spell_evasion' => 0.10,
            'item_prefix_id' => $itemAffix->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'ring-one')->getCharacter();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getAffixReduction('str_reduction');

        $this->assertEquals(0.10, $reduction);
    }

    public function testGetRingReductionUsesEquippedClassSpecial()
    {
        $item = $this->createItem([
            'type' => 'ring',
            'spell_evasion' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'ring-one')->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'spell_evasion' => 0.20,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getRingReduction('spell_evasion');

        $this->assertEqualsWithDelta(0.30, $reduction, 0.000001);
    }

    public function testGetRingReductionIgnoresUnequippedClassSpecial()
    {
        $item = $this->createItem([
            'type' => 'ring',
            'spell_evasion' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'ring-one')->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'spell_evasion' => 0.20,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => false,
        ]);

        $character = $character->refresh();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getRingReduction('spell_evasion');

        $this->assertEqualsWithDelta(0.10, $reduction, 0.000001);
    }

    public function testGetAffixReductionUsesEquippedClassSpecial()
    {
        $itemAffix = $this->createItemAffix([
            'skill_reduction' => 0.10,
        ]);

        $item = $this->createItem([
            'type' => 'ring',
            'item_prefix_id' => $itemAffix->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'ring-one')->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'skill_reduction' => 0.20,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getAffixReduction('skill_reduction');

        $this->assertEqualsWithDelta(0.30, $reduction, 0.000001);
    }

    public function testGetAffixReductionIgnoresUnequippedClassSpecial()
    {
        $itemAffix = $this->createItemAffix([
            'skill_reduction' => 0.10,
        ]);

        $item = $this->createItem([
            'type' => 'ring',
            'item_prefix_id' => $itemAffix->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'ring-one')->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'skill_reduction' => 0.20,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => false,
        ]);

        $character = $character->refresh();

        $reduction = $this->characterStatBuilder->setCharacter($character)->reductionInfo()->getAffixReduction('skill_reduction');

        $this->assertEqualsWithDelta(0.10, $reduction, 0.000001);
    }
}
