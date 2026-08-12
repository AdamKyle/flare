<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\MarketBoard;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use Exception;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class MultiInventoryActionServiceTest extends TestCase
{
    use CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

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

    public function test_bulk_inventory_selling_with_exclude_keeps_the_excluded_slot(): void
    {
        $keepItem = $this->createItem(['cost' => 100]);
        $sellItem = $this->createItem(['cost' => 200]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($keepItem)
            ->giveItem($sellItem)
            ->getCharacter();
        $keepSlotId = $character->inventory->slots->firstWhere('item_id', $keepItem->id)->id;

        resolve(MultiInventoryActionService::class)->sellManyItems($character, ['exclude' => [$keepSlotId]]);

        $this->assertSame(1, $character->refresh()->inventory->slots()->count());
        $this->assertSame($keepSlotId, $character->inventory->slots->first()->id);
    }

    public function test_bulk_set_slot_selling_rejects_a_set_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = resolve(MultiInventoryActionService::class)->sellManySetSlots($character, $otherSet, []);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_bulk_set_slot_selling_updates_gold_and_deletes_selected_set_slots(): void
    {
        $firstItem = $this->createItem(['cost' => 100]);
        $secondItem = $this->createItem(['cost' => 200]);
        $character = $this->character;
        $character->update(['inventory_max' => 0]);
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $firstSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $firstItem->id]);
        $secondSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $secondItem->id]);

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
        $character = $this->character;
        $character->update(['inventory_max' => 0]);
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $slot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        resolve(MultiInventoryActionService::class)->disenchantManySetSlots($character, $set, [$slot->id]);

        $this->assertSame(0, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        Bus::assertDispatched(DisenchantMany::class);
    }

    public function test_bulk_set_slot_disenchanting_rejects_a_set_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = resolve(MultiInventoryActionService::class)->disenchantManySetSlots($character, $otherSet, []);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_bulk_set_slot_destroying_rejects_a_set_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = resolve(MultiInventoryActionService::class)->destroyManySetSlots($character, $otherSet, []);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_bulk_set_slot_destroying_deletes_only_selected_owned_set_slots(): void
    {
        $firstItem = $this->createItem(['type' => 'trinket']);
        $secondItem = $this->createItem();
        $thirdItem = $this->createItem();
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
        $selectedTrinketSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $firstItem->id]);
        $selectedItemSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $secondItem->id]);
        $unselectedSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $thirdItem->id]);
        $otherSlot = $this->createInventorySetSlot(['inventory_set_id' => $otherSet->id, 'item_id' => $thirdItem->id]);

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

        resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $set);

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

        resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $craftedSet);

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

        $result = resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function test_destroy_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $item = $this->createItem();
        $character = $this->character;
        $normalSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $this->createInventorySetSlot(['inventory_set_id' => $normalSet->id, 'item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->destroyAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
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

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

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

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

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

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

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

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

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

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

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

        resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $alchemySlot->id)->exists());
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_sell_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $item = $this->createItem();
        $character = $this->character;
        $normalSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $this->createInventorySetSlot(['inventory_set_id' => $normalSet->id, 'item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
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

        $result = resolve(MultiInventoryActionService::class)->sellAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_queues_every_enchanted_eligible_item_and_dispatches_disenchant_many(): void
    {
        Bus::fake();

        $prefix = $this->createItemAffix(['name' => 'Disenchant All Prefix', 'type' => 'prefix']);
        $enchantedWeapon = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $character = $this->character;
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $enchantedSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $enchantedWeapon->id]);

        $result = resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertSame(200, $result['status']);
        $this->assertFalse($set->slots()->where('id', $enchantedSlot->id)->exists());
        Bus::assertDispatched(DisenchantMany::class);
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_unenchanted_items(): void
    {
        $unenchantedWeapon = $this->createItem(['type' => 'weapon', 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $character = $this->character;
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $unenchantedSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $unenchantedWeapon->id]);

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $unenchantedSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_alchemy_items(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Alchemy Prefix', 'type' => 'prefix']);
        $alchemyItem = $this->createItem(['type' => 'alchemy', 'item_prefix_id' => $prefix->id]);
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

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $alchemySlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_gems(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Gem Prefix', 'type' => 'prefix']);
        $gem = $this->createItem(['type' => 'gem', 'item_prefix_id' => $prefix->id]);
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

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $gemSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_quest_items(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Quest Prefix', 'type' => 'prefix']);
        $questItem = $this->createItem(['type' => 'quest', 'item_prefix_id' => $prefix->id]);
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

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $questSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_artifacts(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Artifact Prefix', 'type' => 'prefix']);
        $artifact = $this->createItem(['type' => 'artifact', 'item_prefix_id' => $prefix->id]);
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

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $artifactSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_keeps_trinkets(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Trinket Prefix', 'type' => 'prefix']);
        $trinket = $this->createItem(['type' => 'trinket', 'item_prefix_id' => $prefix->id]);
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

        resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $trinketSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_rejects_normal_set_target(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Normal Set Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
        $character = $this->character;
        $normalSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => 'Set 1',
            'is_equipped' => false,
            'can_be_equipped' => true,
        ]);
        $normalSetSlot = $this->createInventorySetSlot(['inventory_set_id' => $normalSet->id, 'item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $normalSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($normalSet->slots()->where('id', $normalSetSlot->id)->exists());
    }

    public function test_disenchant_all_crafted_items_set_slots_does_not_disenchant_another_characters_set_slots(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Disenchant Other Character Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
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

        $result = resolve(MultiInventoryActionService::class)->disenchantAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }

    public function test_destroy_many_items_removes_only_the_selected_slots(): void
    {
        $keepItem = $this->createItem();
        $destroyItem = $this->createItem();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($keepItem)
            ->giveItem($destroyItem)
            ->getCharacter();
        $destroySlotId = $character->inventory->slots()->where('item_id', $destroyItem->id)->first()->id;
        $keepSlotId = $character->inventory->slots()->where('item_id', $keepItem->id)->first()->id;

        resolve(MultiInventoryActionService::class)->destroyManyItems($character, ['ids' => [$destroySlotId]]);

        $this->assertSame(0, $character->inventory->slots()->where('id', $destroySlotId)->count());
        $this->assertSame(1, $character->inventory->slots()->where('id', $keepSlotId)->count());
    }

    public function test_destroy_many_items_with_exclude_keeps_only_the_excluded_slots(): void
    {
        $keepItem = $this->createItem();
        $destroyItem = $this->createItem();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($keepItem)
            ->giveItem($destroyItem)
            ->getCharacter();
        $destroySlotId = $character->inventory->slots()->where('item_id', $destroyItem->id)->first()->id;
        $keepSlotId = $character->inventory->slots()->where('item_id', $keepItem->id)->first()->id;

        resolve(MultiInventoryActionService::class)->destroyManyItems($character, ['exclude' => [$keepSlotId]]);

        $this->assertSame(0, $character->inventory->slots()->where('id', $destroySlotId)->count());
        $this->assertSame(1, $character->inventory->slots()->where('id', $keepSlotId)->count());
    }

    public function test_move_many_items_to_selected_set_moves_all_selected_items_and_reports_the_destination_set(): void
    {
        $firstItem = $this->createItem();
        $secondItem = $this->createItem();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($firstItem)
            ->giveItem($secondItem)
            ->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $result = resolve(MultiInventoryActionService::class)->moveManyItemsToSelectedSet($character, $set->id, $slotIds);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Moved all selected items to: Set 0.', $result['message']);
        $this->assertSame(2, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->inventory->slots()->count());
    }

    public function test_move_many_items_to_selected_set_stops_and_returns_error_when_a_move_fails(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $validSlotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = resolve(MultiInventoryActionService::class)->moveManyItemsToSelectedSet($character, $set->id, [999999, $validSlotId]);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Either the slot or the inventory set does not exist.', $result['message']);
        $this->assertSame(1, $character->inventory->slots()->where('id', $validSlotId)->count());
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_equip_many_items_equips_matching_items(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = resolve(MultiInventoryActionService::class)->equipManyItems($character, [$slotId]);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Equipped valid items to your character.', $result['message']);
        $this->assertTrue($character->inventory->slots()->where('id', $slotId)->first()->equipped);
    }

    public function test_equip_many_items_returns_error_result_when_equip_many_builder_throws(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $equipManyBuilder = Mockery::mock(EquipManyBuilder::class);
        $equipManyBuilder->shouldReceive('buildEquipmentArray')->once()->andThrow(new Exception('Something went wrong building the equipment array.'));

        $this->app->instance(EquipManyBuilder::class, $equipManyBuilder);

        $result = resolve(MultiInventoryActionService::class)->equipManyItems($character, [123]);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Something went wrong building the equipment array.', $result['message']);
    }

    public function test_list_many_set_slots_lists_eligible_items_on_the_market_board_and_removes_them_from_the_set(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $character = $this->character;
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $slot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $result = resolve(MultiInventoryActionService::class)->listManySetSlots($character, $set, [$slot->id], 500);

        $this->assertSame(200, $result['status']);
        $this->assertSame(1, MarketBoard::where('character_id', $character->id)->where('item_id', $item->id)->where('listed_price', 500)->count());
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_list_many_set_slots_rejects_a_set_not_owned_by_the_character(): void
    {
        $character = $this->character;
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $otherSet = $this->createInventorySet(['character_id' => $otherCharacter->id]);

        $result = resolve(MultiInventoryActionService::class)->listManySetSlots($character, $otherSet, [], 100);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Cannot do that.', $result['message']);
    }

    public function test_disenchant_many_items_returns_no_eligible_items_message_when_nothing_matches(): void
    {
        $character = $this->character;

        $result = resolve(MultiInventoryActionService::class)->disenchantManyItems($character, []);

        $this->assertSame(200, $result['status']);
        $this->assertSame('No eligible items to disenchant.', $result['message']);
    }
}
