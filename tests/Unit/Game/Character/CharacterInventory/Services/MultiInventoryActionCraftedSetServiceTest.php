<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\CharacterInventoryServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class MultiInventoryActionCraftedSetServiceTest extends TestCase
{
    use CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?MultiInventoryActionService $multiInventoryActionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->multiInventoryActionService = (new CharacterInventoryServiceFactory)->buildMultiInventoryActionService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->character = null;
        $this->multiInventoryActionService = null;
    }

    public function test_destroy_all_crafted_items_set_slots_removes_every_trinket_and_non_trinket_slot(): void
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $nonTrinket = $this->createItem();
        $character = $this->character;
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $trinket->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $nonTrinket->id]);

        $this->multiInventoryActionService->destroyAllCraftedItemsSetSlots($character, $set);

        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_destroy_all_crafted_items_set_slots_does_not_remove_normal_set_slots(): void
    {
        $item = $this->createItem();
        $character = $this->character;
        $craftedSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $normalSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $this->createInventorySetSlot(['inventory_set_id' => $normalSet->id, 'item_id' => $item->id]);

        $this->multiInventoryActionService->destroyAllCraftedItemsSetSlots($character, $craftedSet);

        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_destroy_all_crafted_items_set_slots_does_not_remove_another_characters_set_slots(): void
    {
        $item = $this->createItem();
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSet = $this->createInventorySet([
            'character_id' => $otherCharacter->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSlot = $this->createInventorySetSlot(['inventory_set_id' => $otherSet->id, 'item_id' => $item->id]);

        $result = $this->multiInventoryActionService->destroyAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function test_sell_all_crafted_items_set_slots_sells_every_eligible_item_and_increases_gold_by_eligible_sale_values_only(): void
    {
        $weapon = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $armour = $this->createItem(['type' => 'armour', 'cost' => 200]);
        $character = $this->character;
        $character->update(['gold' => 0]);
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $weapon->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $armour->id]);

        $expectedGold = SellItemCalculator::fetchSalePriceWithAffixes($weapon) + SellItemCalculator::fetchSalePriceWithAffixes($armour);

        $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame($expectedGold, $character->refresh()->gold);
    }

    public function test_sell_all_crafted_items_set_slots_keeps_trinkets(): void
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = $this->character;
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $trinketSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $trinket->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $weapon->id]);

        $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $trinketSlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_keeps_artifacts(): void
    {
        $artifact = $this->createItem(['type' => 'artifact']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = $this->character;
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $artifactSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $artifact->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $weapon->id]);

        $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $artifactSlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_keeps_quest_items(): void
    {
        $questItem = $this->createItem(['type' => 'quest']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = $this->character;
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $questSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $questItem->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $weapon->id]);

        $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $questSlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_keeps_gems(): void
    {
        $gem = $this->createItem(['type' => 'gem']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = $this->character;
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $gemSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $gem->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $weapon->id]);

        $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $gemSlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_keeps_alchemy_items(): void
    {
        $alchemyItem = $this->createItem(['type' => 'alchemy']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = $this->character;
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $alchemySlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $alchemyItem->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $weapon->id]);

        $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $alchemySlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_does_not_sell_another_characters_set_slots(): void
    {
        $item = $this->createItem();
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet([
            'character_id' => $otherCharacter->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSlot = $this->createInventorySetSlot(['inventory_set_id' => $otherSet->id, 'item_id' => $item->id]);

        $result = $this->multiInventoryActionService->sellAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }
}
