<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\InventorySet;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class CharacterInventoryControllerTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateCharacterAutomation, CreateInventorySets, CreateItem, RefreshDatabase;

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

    public function test_use_item_endpoint_succeeds_for_alchemy_boon_during_automation(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/use-item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Used selected item.', $jsonData['message']);
    }

    public function test_use_item_endpoint_returns_unprocessable_for_non_boon_item_during_automation(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'weapon',
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/use-item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            'No you are busy, you can use Alchemy items that apply boons to your character. Please cancel your: Exploration, if you want to use this.',
            $jsonData['message']
        );
    }

    public function test_inventory_returns_paginated_inventory(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory');

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_quest_items_returns_paginated_quest_items(): void
    {
        $item = $this->createItem(['type' => 'quest']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/quest-items');

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_usable_items_returns_paginated_usable_items(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/usable-items');

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_equipped_items_returns_equipped_items_and_damage_info(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item, true, 'body')->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/equipped_items');

        $response->assertOk();
        $this->assertArrayHasKey('equipped', $response->json());
        $this->assertArrayHasKey('weapon_damage', $response->json());
        $this->assertArrayHasKey('set_name', $response->json());
    }

    public function test_equipped_items_returns_the_real_healing_amount_for_an_equipped_healing_spell(): void
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => ItemType::SPELL_HEALING->value,
            'base_healing' => 100,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'spell-one')
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/equipped_items');

        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('healing_amount'));
    }

    public function test_equipped_items_returns_an_empty_data_array_when_nothing_is_equipped(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/equipped_items');

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json('equipped'));
        $this->assertSame([], $response->json('equipped.data'));
    }

    public function test_current_sets_returns_paginated_inventory_sets(): void
    {
        $character = $this->character->getCharacter();
        $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/sets');

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_set_options_returns_paginated_lean_set_options(): void
    {
        $character = $this->character->getCharacter();
        $eligibleSet = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Lean Option Set', 'is_equipped' => false]);

        $nonEmptySet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => false]);
        $this->createInventorySetSlot(['inventory_set_id' => $nonEmptySet->id, 'item_id' => $this->createItem()->id]);

        $equippedSet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);

        $batchCraftingSet = $this->createInventorySet([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
            'is_equipped' => false,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/set-options');

        $response->assertOk();
        $data = $response->json()['data'];
        $returnedSetIds = array_column($data, 'set_id');

        $this->assertArrayHasKey('data', $response->json());
        $this->assertArrayHasKey('set_id', $data[0]);
        $this->assertArrayHasKey('display_name', $data[0]);
        $this->assertArrayHasKey('set_number', $data[0]);
        $this->assertArrayNotHasKey('items', $data[0]);
        $this->assertContains($eligibleSet->id, $returnedSetIds);
        $this->assertNotContains($nonEmptySet->id, $returnedSetIds);
        $this->assertNotContains($equippedSet->id, $returnedSetIds);
        $this->assertNotContains($batchCraftingSet->id, $returnedSetIds);
    }

    public function test_get_set_items_returns_paginated_set_items(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/set-items?filters[set_id]='.$set->id);

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_item_details_returns_item_data_for_a_valid_slot(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/item?slot_id='.$slotId);

        $response->assertOk();
    }

    public function test_item_details_returns_422_when_slot_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory/item?slot_id=999999');

        $response->assertStatus(422);
        $this->assertSame("There's nothing here for that slot.", $response->json('message'));
    }

    public function test_inventory_set_equippability_details_returns_type_counts(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem(['type' => 'body']);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/inventory-set-equippability-details/'.$set->id);

        $response->assertOk();
    }

    public function test_equip_item_equips_a_matching_item(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/equip-item', [
                'position' => 'body',
                'slot_id' => $slotId,
                'equip_type' => 'body',
            ]);

        $response->assertOk();
        $this->assertTrue($character->inventory->slots()->where('id', $slotId)->first()->equipped);
    }

    public function test_save_equipped_as_set_moves_equipped_items_into_the_set(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item, true, 'body')->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/save-equipped-as-set', [
                'move_to_set' => $set->id,
            ]);

        $response->assertOk();
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_unequip_item_unequips_the_selected_item(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item, true, 'body')->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/unequip', [
                'item_to_remove' => $slotId,
            ]);

        $response->assertOk();
        $this->assertFalse($character->inventory->slots()->where('id', $slotId)->first()->equipped);
    }

    public function test_unequip_item_unequips_the_equipped_set_when_set_is_equipped(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem(['type' => 'body']);
        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/unequip', [
                'inventory_set_equipped' => true,
            ]);

        $response->assertOk();
        $this->assertFalse($set->refresh()->is_equipped);
    }

    public function test_unequip_all_unequips_every_equipped_item(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item, true, 'body')->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/unequip-all');

        $response->assertOk();
        $this->assertSame(0, $character->inventory->slots()->where('equipped', true)->count());
    }

    public function test_unequip_all_unequips_the_equipped_set_when_set_is_equipped(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem(['type' => 'body']);
        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/unequip-all', [
                'is_set_equipped' => true,
            ]);

        $response->assertOk();
        $this->assertFalse($set->refresh()->is_equipped);
    }

    public function test_equip_item_set_equips_the_selected_set(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/equip/'.$set->id);

        $response->assertOk();
        $this->assertTrue($set->refresh()->is_equipped);
    }

    public function test_use_many_items_uses_the_selected_alchemy_items(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
        ]);
        $character = $this->character->getCharacter();
        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/use-many-items', [
                'items_to_use' => [$alchemySlot->id],
            ]);

        $response->assertOk();
    }

    public function test_use_many_items_accepts_repeated_owned_alchemy_slot_ids(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'can_stack' => true,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
        ]);
        $character = $this->character->getCharacter();
        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/use-many-items', [
                'items_to_use' => [$alchemySlot->id, $alchemySlot->id],
            ]);

        $response->assertOk();
        $this->assertNotNull($response->json('message'));
    }

    public function test_use_many_items_rejects_a_non_array_items_to_use_payload(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/use-many-items', [
                'items_to_use' => 'not-an-array',
            ]);

        $response->assertStatus(422);
    }

    public function test_use_alchemy_item_uses_a_single_alchemy_item(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
        ]);
        $character = $this->character->getCharacter();
        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/use-alchemy-item/'.$alchemySlot->id);

        $response->assertOk();
    }

    public function test_use_alchemy_item_endpoint_rejects_a_gem_scroll_item(): void
    {
        Queue::fake();

        $item = $this->createGemXpScrollItem();
        $character = $this->character->getCharacter();
        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/use-alchemy-item/'.$alchemySlot->id);

        $response->assertUnprocessable();
        $this->assertEmpty($character->refresh()->boons);
    }

    public function test_use_alchemy_item_uses_all_when_use_all_flag_is_set(): void
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'can_stack' => true,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
        ]);
        $character = $this->character->getCharacter();
        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 3,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/use-alchemy-item/'.$alchemySlot->id, [
                'use_all' => true,
            ]);

        $response->assertOk();
    }

    public function test_destroy_all_alchemy_items_removes_all_alchemy_slots(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/destroy-all-alchemy-items');

        $response->assertOk();
        $this->assertSame(0, $character->alchemyBag->slots()->count());
    }

    public function test_destroy_alchemy_item_removes_the_selected_alchemy_slot(): void
    {
        $item = $this->createItem(['type' => 'alchemy', 'usable' => true]);
        $character = $this->character->getCharacter();
        $alchemySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/destroy-alchemy-item', [
                'slot_id' => $alchemySlot->id,
            ]);

        $response->assertOk();
        $this->assertNull($alchemySlot->fresh());
    }

    public function test_destroy_removes_a_matching_item(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/destroy', [
                'item_id' => $item->id,
            ]);

        $response->assertOk();
        $this->assertSame(0, $character->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function test_destroy_is_blocked_by_batch_crafting_automation(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/destroy', [
                'item_id' => $item->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_remove_from_set_moves_the_item_back_into_the_inventory(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/remove', [
                'inventory_set_id' => $set->id,
                'slot_id' => $setSlot->id,
            ]);

        $response->assertOk();
        $this->assertSame(1, $character->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function test_empty_set_moves_all_items_back_into_the_inventory(): void
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/'.$set->id.'/remove-all');

        $response->assertOk();
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_destroy_all_removes_every_item_in_the_inventory(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/destroy-all');

        $response->assertOk();
        $this->assertSame(0, $character->inventory->slots()->count());
    }

    public function test_disenchant_all_returns_a_message_when_there_is_nothing_to_disenchant(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/disenchant-all');

        $response->assertOk();
        $this->assertSame('You have nothing to disenchant.', $response->json('message'));
    }

    public function test_destroy_all_is_blocked_by_batch_crafting_automation(): void
    {
        $character = $this->character->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/destroy-all');

        $response->assertStatus(422);
    }

    public function test_disenchant_all_is_blocked_by_batch_crafting_automation(): void
    {
        $character = $this->character->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/disenchant-all');

        $response->assertStatus(422);
    }

    public function test_move_to_set_moves_the_item_into_the_selected_set(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/move-to-set', [
                'set_id' => $set->id,
                'slot_id' => $slotId,
            ]);

        $response->assertOk();
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function test_rename_set_updates_the_set_name(): void
    {
        $character = $this->character->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory-set/rename-set', [
                'set_id' => $set->id,
                'set_name' => 'My Favorite Set',
            ]);

        $response->assertOk();
        $this->assertSame('My Favorite Set', $set->refresh()->name);
    }

    public function test_sell_item_removes_the_item_and_sells_it(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/sell-item', [
                'item_id' => $item->id,
            ]);

        $response->assertOk();
        $this->assertSame(0, $character->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function test_disenchant_item_returns_422_when_item_is_not_found(): void
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/disenchant-item', [
                'item_id' => 999999,
            ]);

        $response->assertStatus(422);
        $this->assertSame('No item found to disenchant.', $response->json('message'));
    }

    public function test_move_item_to_set_moves_the_item_into_the_selected_set(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;
        $set = $this->createInventorySet(['character_id' => $character->id]);

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/inventory/move-item-to-set', [
                'set_id' => $set->id,
                'slot_id' => $slotId,
            ]);

        $response->assertOk();
        $this->assertSame(1, $set->refresh()->slots()->count());
    }
}
