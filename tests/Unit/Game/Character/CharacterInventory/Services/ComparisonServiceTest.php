<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Items\Values\ItemType;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\SpellTypes;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ComparisonServiceTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ComparisonService $comparisonService;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->comparisonService = resolve(ComparisonService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->comparisonService = null;
    }

    public function testItemComparisonDetailsIsEmptyWhenNothingEquipped()
    {
        $item = $this->createItem(['type' => ItemType::WAND->value]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot, ItemType::DAGGER->value);

        $this->assertEmpty($comparisonData['details']);
    }

    public function testItemComparisonDetailsIsEmptyWhenSomethingEquippedButComparingForQuest()
    {
        $item = $this->createItem(['type' => 'quest']);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem(
            $this->createItem([
                'type' => ItemType::SWORD->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]), true, 'left-hand'
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot, ItemType::SWORD->value);

        $this->assertEmpty($comparisonData['details']);
        $this->assertEquals($item->affix_name, $comparisonData['itemToEquip']['name']);
    }

    public function testItemComparisonDetailsIsEmptyWhenSomethingEquippedButComparingForUsableItem()
    {
        $item = $this->createItem(['type' => 'alchemy']);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem(
            $this->createItem([
                'type' => ItemType::WAND->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]), true, 'left-hand'
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot, ItemType::STAVE->value);

        $this->assertEmpty($comparisonData['details']);
        $this->assertEquals($item->affix_name, $comparisonData['itemToEquip']['affix_name']);
    }

    public function testItemComparisonDetailsIsNotEmptyWhenSomethingEquipped()
    {
        $item = $this->createItem(['type' => ItemType::SWORD->value]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem(
            $this->createItem([
                'type' => ItemType::SWORD->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]), true, 'left-hand'
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot, ItemType::SWORD->value);

        $this->assertNotEmpty($comparisonData['details']);
    }

    public function testBuildShopDataForBow()
    {
        $item = $this->createItem(['type' => ItemType::BOW->value]);

        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type' => ItemType::SWORD->value,
                'base_damage' => 25,
                'str_mod' => 0.10,
            ]), true, 'left-hand'
        )->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item, ItemType::BOW->value);

        $this->assertEquals($item->affix_name, $comparisonData['itemToEquip']['affix_name']);
    }

    public function testBuildShopDataForArmourType()
    {
        $item = $this->createItem(['type' => ArmourTypes::SHIELD]);

        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type' => ArmourTypes::SHIELD,
                'base_ac' => 25,
                'str_mod' => 0.10,
            ]), true, 'left-hand'
        )->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item, ArmourTypes::SHIELD);

        $this->assertEquals($item->affix_name, $comparisonData['itemToEquip']['affix_name']);
    }

    public function testBuildShopDataForSpell()
    {
        $item = $this->createItem(['type' => ItemType::SPELL_DAMAGE->value]);

        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type' => SpellTypes::HEALING,
                'base_healing' => 25,
                'str_mod' => 0.10,
            ]), true, 'spell-one'
        )->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item, ItemType::SPELL_DAMAGE->value);

        $this->assertEquals($item->affix_name, $comparisonData['itemToEquip']['affix_name']);
    }

    public function testBuildShopDataForSpellInEquippedSet()
    {
        $item = $this->createItem(['type' => ItemType::SPELL_DAMAGE->value]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($this->createItem([
            'type' => SpellTypes::HEALING,
            'base_healing' => 25,
            'str_mod' => 0.10,
        ]), 0, 'spell-one')->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item, ItemType::SPELL_DAMAGE->value);

        $this->assertEquals($item->affix_name, $comparisonData['itemToEquip']['affix_name']);
    }
}
