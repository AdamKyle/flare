<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\InventorySet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterInventoryMultiControllerTest extends TestCase
{
    use CreateBatchCrafting, CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_equip_selected_equips_matching_items(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/equip-selected', [
                'mode' => 'include',
                'slot_ids' => [$slotId],
            ]);

        $response->assertOk();
        $this->assertTrue($character->inventory->slots()->where('id', $slotId)->first()->equipped);
    }

    public function test_move_selected_moves_items_into_the_chosen_set(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/move-selected', [
                'set_id' => $set->id,
                'slot_ids' => [$slotId],
            ]);

        $response->assertOk();
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_destroy_selected_removes_matching_slots(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/destroy-selected', [
                'mode' => 'include',
                'ids' => [$slotId],
            ]);

        $response->assertOk();
        $this->assertSame(0, $character->inventory->slots()->where('id', $slotId)->count());
    }

    public function test_sell_selected_sells_matching_slots(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/sell-selected', [
                'mode' => 'include',
                'ids' => [$slotId],
            ]);

        $response->assertOk();
        $this->assertSame(0, $character->inventory->slots()->where('id', $slotId)->count());
    }

    public function test_disenchant_selected_dispatches_disenchant_job(): void
    {
        $prefix = $this->createItemAffix();
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/disenchant-selected', [
                'mode' => 'include',
                'ids' => [$slotId],
            ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('disenchanted_item'));
        $this->assertSame(0, $character->inventory->slots()->where('id', $slotId)->count());
    }

    public function test_sell_selected_from_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/sell-selected', [
                'set_id' => 999999,
                'slot_ids' => [1],
            ]);

        $response->assertStatus(422);
        $this->assertSame('Cannot do that.', $response->json('message'));
    }

    public function test_disenchant_selected_from_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/disenchant-selected', [
                'set_id' => 999999,
                'slot_ids' => [1],
            ]);

        $response->assertStatus(422);
        $this->assertSame('Cannot do that.', $response->json('message'));
    }

    public function test_destroy_selected_from_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/destroy-selected', [
                'set_id' => 999999,
                'slot_ids' => [1],
            ]);

        $response->assertStatus(422);
        $this->assertSame('Cannot do that.', $response->json('message'));
    }

    public function test_destroy_all_from_set_removes_all_slots(): void
    {
        $item = $this->createItem();
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/destroy-all', [
                'set_id' => $set->id,
            ]);

        $response->assertOk();
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_destroy_all_from_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/destroy-all', [
                'set_id' => 999999,
            ]);

        $response->assertStatus(422);
        $this->assertSame('Cannot do that.', $response->json('message'));
    }

    public function test_sell_all_from_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/sell-all', [
                'set_id' => 999999,
            ]);

        $response->assertStatus(422);
        $this->assertSame('Cannot do that.', $response->json('message'));
    }

    public function test_sell_all_from_set_sells_all_slots(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/sell-all', [
                'set_id' => $set->id,
            ]);

        $response->assertOk();
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_disenchant_all_from_set_returns_error_when_set_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/disenchant-all', [
                'set_id' => 999999,
            ]);

        $response->assertStatus(422);
        $this->assertSame('Cannot do that.', $response->json('message'));
    }

    public function test_sell_selected_from_set_sells_matching_set_slots(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/sell-selected', [
                'set_id' => $set->id,
                'slot_ids' => [$setSlot->id],
            ]);

        $response->assertOk();
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_disenchant_selected_from_set_disenchants_matching_set_slots(): void
    {
        $prefix = $this->createItemAffix();
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/disenchant-selected', [
                'set_id' => $set->id,
                'slot_ids' => [$setSlot->id],
            ]);

        $response->assertOk();
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_destroy_selected_from_set_removes_matching_set_slots(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/destroy-selected', [
                'set_id' => $set->id,
                'slot_ids' => [$setSlot->id],
            ]);

        $response->assertOk();
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_disenchant_all_from_set_disenchants_every_eligible_slot(): void
    {
        $prefix = $this->createItemAffix();
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/disenchant-all', [
                'set_id' => $set->id,
            ]);

        $response->assertOk();
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_sell_selected_from_set_is_blocked_by_batch_crafting_automation(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/sell-selected', [
                'set_id' => $set->id,
                'slot_ids' => [$setSlot->id],
            ]);

        $response->assertStatus(422);
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_destroy_selected_is_blocked_by_batch_crafting_automation(): void
    {
        $character = $this->character->getCharacter();

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/destroy-selected', [
                'mode' => 'include',
                'ids' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_sell_selected_is_blocked_by_batch_crafting_automation(): void
    {
        $character = $this->character->getCharacter();

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/sell-selected', [
                'mode' => 'include',
                'ids' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_disenchant_selected_is_blocked_by_batch_crafting_automation(): void
    {
        $character = $this->character->getCharacter();

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/disenchant-selected', [
                'mode' => 'include',
                'ids' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_disenchant_selected_from_set_is_blocked_by_batch_crafting_automation(): void
    {
        $prefix = $this->createItemAffix();
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/disenchant-selected', [
                'set_id' => $set->id,
                'slot_ids' => [$setSlot->id],
            ]);

        $response->assertStatus(422);
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_destroy_selected_from_set_is_blocked_by_batch_crafting_automation(): void
    {
        $item = $this->createItem(['type' => 'weapon']);
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/destroy-selected', [
                'set_id' => $set->id,
                'slot_ids' => [$setSlot->id],
            ]);

        $response->assertStatus(422);
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_destroy_all_from_set_is_blocked_by_batch_crafting_automation(): void
    {
        $character = $this->character->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/destroy-all', [
                'set_id' => 1,
            ]);

        $response->assertStatus(422);
    }

    public function test_sell_all_from_set_is_blocked_by_batch_crafting_automation(): void
    {
        $character = $this->character->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/sell-all', [
                'set_id' => 1,
            ]);

        $response->assertStatus(422);
    }

    public function test_disenchant_all_from_set_is_blocked_by_batch_crafting_automation(): void
    {
        $character = $this->character->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/disenchant-all', [
                'set_id' => 1,
            ]);

        $response->assertStatus(422);
    }
}
