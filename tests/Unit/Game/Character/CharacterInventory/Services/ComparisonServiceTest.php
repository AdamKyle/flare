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

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->comparisonService = resolve(ComparisonService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->comparisonService = null;
    }

    public function test_item_comparison_details_is_empty_when_nothing_equipped()
    {
        $item = $this->createItem(['type' => ItemType::WAND->value]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $comparisonData = $this->comparisonService->buildComparisonData($character, $slot, ItemType::DAGGER->value);

        $this->assertEmpty($comparisonData['details']);
    }

    public function test_item_comparison_details_is_empty_when_something_equipped_but_comparing_for_quest()
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

    public function test_item_comparison_details_is_empty_when_something_equipped_but_comparing_for_usable_item()
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

    public function test_item_comparison_details_is_not_empty_when_something_equipped()
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

    public function test_build_shop_data_for_bow()
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

        $this->assertArrayHasKey('details', $comparisonData);
        $this->assertIsArray($comparisonData['details']);
        $this->assertNotEmpty($comparisonData['details']);
    }

    public function test_build_shop_data_for_armour_type()
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

        $this->assertArrayHasKey('details', $comparisonData);
        $this->assertIsArray($comparisonData['details']);
        $this->assertNotEmpty($comparisonData['details']);
    }

    public function test_build_shop_data_for_spell()
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

        $this->assertArrayHasKey('details', $comparisonData);
        $this->assertIsArray($comparisonData['details']);
        $this->assertNotEmpty($comparisonData['details']);
    }

    public function test_build_shop_data_for_spell_in_equipped_set()
    {
        $item = $this->createItem(['type' => ItemType::SPELL_DAMAGE->value]);

        $manager = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($this->createItem([
                'type' => SpellTypes::HEALING,
                'base_healing' => 25,
                'str_mod' => 0.10,
            ]), 0, 'spell-one', true);

        $character = $manager->getCharacter();

        $comparisonData = $this->comparisonService->buildShopData($character, $item, ItemType::SPELL_DAMAGE->value);

        $this->assertArrayHasKey('details', $comparisonData);
        $this->assertIsArray($comparisonData['details']);
        $this->assertNotEmpty($comparisonData['details']);
    }
}
