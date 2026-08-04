<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class MultiInventoryActionServiceTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    public function test_bulk_inventory_selling_updates_gold_and_deletes_selected_slots(): void
    {
        $firstItem = $this->createItem(['cost' => 100]);
        $secondItem = $this->createItem(['cost' => 200]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($firstItem)
            ->giveItem($secondItem)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        resolve(MultiInventoryActionService::class)->sellManyItems($character, $slotIds);

        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        $this->assertSame(295, $character->refresh()->gold);
    }

    public function test_bulk_set_slot_selling_updates_gold_and_deletes_selected_set_slots(): void
    {
        $firstItem = $this->createItem(['cost' => 100]);
        $secondItem = $this->createItem(['cost' => 200]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['inventory_max' => 0]);
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $firstSlot = $set->slots()->create(['item_id' => $firstItem->id]);
        $secondSlot = $set->slots()->create(['item_id' => $secondItem->id]);

        resolve(MultiInventoryActionService::class)->sellManySetSlots($character, $set, [$firstSlot->id, $secondSlot->id]);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        $this->assertSame(295, $character->refresh()->gold);
    }

    public function test_bulk_set_slot_disenchanting_deletes_selected_set_slots_and_dispatches_disenchant_many(): void
    {
        Bus::fake();

        $prefix = $this->createItemAffix(['name' => 'Bulk Disenchant Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['cost' => 100, 'item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['inventory_max' => 0]);
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $slot = $set->slots()->create(['item_id' => $item->id]);

        resolve(MultiInventoryActionService::class)->disenchantManySetSlots($character, $set, [$slot->id]);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        Bus::assertDispatched(DisenchantMany::class);
    }

    public function test_bulk_set_slot_destroying_deletes_only_selected_owned_set_slots(): void
    {
        $firstItem = $this->createItem(['type' => 'trinket']);
        $secondItem = $this->createItem();
        $thirdItem = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSet = $otherCharacter->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $selectedTrinketSlot = $set->slots()->create(['item_id' => $firstItem->id]);
        $selectedItemSlot = $set->slots()->create(['item_id' => $secondItem->id]);
        $unselectedSlot = $set->slots()->create(['item_id' => $thirdItem->id]);
        $otherSlot = $otherSet->slots()->create(['item_id' => $thirdItem->id]);

        resolve(MultiInventoryActionService::class)->destroyManySetSlots($character, $set, [
            $selectedTrinketSlot->id,
            $selectedItemSlot->id,
            $otherSlot->id,
        ]);

        $this->assertFalse($set->slots()->where('id', $selectedTrinketSlot->id)->exists());
        $this->assertFalse($set->slots()->where('id', $selectedItemSlot->id)->exists());
        $this->assertTrue($set->slots()->where('id', $unselectedSlot->id)->exists());
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function test_destroy_all_crafted_items_set_slots_removes_every_trinket_and_non_trinket_slot(): void
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $nonTrinket = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $set->slots()->create(['item_id' => $trinket->id]);
        $set->slots()->create(['item_id' => $nonTrinket->id]);

        resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $set);

        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_destroy_all_crafted_items_set_slots_does_not_remove_normal_set_slots(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $craftedSet = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $normalSet = $character->inventorySets()->create([
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $normalSet->slots()->create(['item_id' => $item->id]);

        resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $craftedSet);

        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_destroy_all_crafted_items_set_slots_does_not_remove_another_characters_set_slots(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSet = $otherCharacter->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSlot = $otherSet->slots()->create(['item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function test_destroy_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $normalSet = $character->inventorySets()->create([
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $normalSet->slots()->create(['item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_sell_all_crafted_items_set_slots_sells_every_eligible_item_and_increases_gold_by_eligible_sale_values_only(): void
    {
        $weapon = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $armour = $this->createItem(['type' => 'armour', 'cost' => 200]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold' => 0]);
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $set->slots()->create(['item_id' => $weapon->id]);
        $set->slots()->create(['item_id' => $armour->id]);

        $expectedGold = SellItemCalculator::fetchSalePriceWithAffixes($weapon) + SellItemCalculator::fetchSalePriceWithAffixes($armour);

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame($expectedGold, $character->refresh()->gold);
    }

    public function test_sell_all_crafted_items_set_slots_keeps_trinkets(): void
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $trinketSlot = $set->slots()->create(['item_id' => $trinket->id]);
        $set->slots()->create(['item_id' => $weapon->id]);

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $trinketSlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_keeps_artifacts(): void
    {
        $artifact = $this->createItem(['type' => 'artifact']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $artifactSlot = $set->slots()->create(['item_id' => $artifact->id]);
        $set->slots()->create(['item_id' => $weapon->id]);

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $artifactSlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_keeps_quest_items(): void
    {
        $questItem = $this->createItem(['type' => 'quest']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $questSlot = $set->slots()->create(['item_id' => $questItem->id]);
        $set->slots()->create(['item_id' => $weapon->id]);

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $questSlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_keeps_gems(): void
    {
        $gem = $this->createItem(['type' => 'gem']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $gemSlot = $set->slots()->create(['item_id' => $gem->id]);
        $set->slots()->create(['item_id' => $weapon->id]);

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $gemSlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_keeps_alchemy_items(): void
    {
        $alchemyItem = $this->createItem(['type' => 'alchemy']);
        $weapon = $this->createItem(['type' => 'weapon']);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $alchemySlot = $set->slots()->create(['item_id' => $alchemyItem->id]);
        $set->slots()->create(['item_id' => $weapon->id]);

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $alchemySlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $normalSet = $character->inventorySets()->create([
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $normalSet->slots()->create(['item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_sell_all_crafted_items_set_slots_does_not_sell_another_characters_set_slots(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $otherCharacter->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSlot = $otherSet->slots()->create(['item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_queues_every_enchanted_eligible_item_and_dispatches_disenchant_many(): void
    {
        Bus::fake();

        $prefix = $this->createItemAffix(['name' => 'Disenchant All Prefix', 'type' => 'prefix']);
        $enchantedWeapon = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $enchantedSlot = $set->slots()->create(['item_id' => $enchantedWeapon->id]);

        $result = resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertSame(200, $result['status']);
        $this->assertFalse($set->slots()->where('id', $enchantedSlot->id)->exists());
        Bus::assertDispatched(DisenchantMany::class);
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_unenchanted_items(): void
    {
        $unenchantedWeapon = $this->createItem(['type' => 'weapon', 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $unenchantedSlot = $set->slots()->create(['item_id' => $unenchantedWeapon->id]);

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $unenchantedSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_alchemy_items(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Alchemy Prefix', 'type' => 'prefix']);
        $alchemyItem = $this->createItem(['type' => 'alchemy', 'item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $alchemySlot = $set->slots()->create(['item_id' => $alchemyItem->id]);

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $alchemySlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_gems(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Gem Prefix', 'type' => 'prefix']);
        $gem = $this->createItem(['type' => 'gem', 'item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $gemSlot = $set->slots()->create(['item_id' => $gem->id]);

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $gemSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_quest_items(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Quest Prefix', 'type' => 'prefix']);
        $questItem = $this->createItem(['type' => 'quest', 'item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $questSlot = $set->slots()->create(['item_id' => $questItem->id]);

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $questSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_artifacts(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Artifact Prefix', 'type' => 'prefix']);
        $artifact = $this->createItem(['type' => 'artifact', 'item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $artifactSlot = $set->slots()->create(['item_id' => $artifact->id]);

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $artifactSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_trinkets(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Trinket Prefix', 'type' => 'prefix']);
        $trinket = $this->createItem(['type' => 'trinket', 'item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $trinketSlot = $set->slots()->create(['item_id' => $trinket->id]);

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $trinketSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Normal Set Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $normalSet = $character->inventorySets()->create([
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $normalSet->slots()->create(['item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_does_not_disenchant_another_characters_set_slots(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Other Character Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $otherCharacter->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSlot = $otherSet->slots()->create(['item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }
}
