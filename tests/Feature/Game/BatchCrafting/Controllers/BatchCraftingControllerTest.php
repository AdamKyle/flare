<?php

namespace Tests\Feature\Game\BatchCrafting\Controllers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Values\AutomationType;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBag;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateInventory;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;

class BatchCraftingControllerTest extends TestCase
{
    use CreateAlchemyBag, CreateAlchemyBagSlot, CreateBatchCrafting, CreateCharacter, CreateCharacterAutomation, CreateInventory, CreateInventorySlot, CreateItem, CreateUser, RefreshDatabase;

    public function testStartCraftBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT->value)->first());
    }

    public function testStartCraftAndEnchantBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::CRAFT_AND_ENCHANT->value)->first());
    }

    public function testStartEnchantBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ENCHANT->value)->first());
    }

    public function testStartAlchemyBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::ALCHEMY->value)->first());
    }

    public function testStartHolyOilsBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::HOLY_OILS->value)->first());
    }

    public function testStartTrinketryBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100, 'gold_dust' => 100, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('batch_type', BatchCraftingType::TRINKETRY->value)->first());
    }

    public function testRejectInvalidDisposition(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => 'invalid',
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectListForCraft(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectListForHolyOils(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectListForTrinketry(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testAllowListForCraftAndEnchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::LIST->value)->first());
    }

    public function testAllowListForEnchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::LIST->value)->first());
    }

    public function testAllowListForAlchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::LIST->value)->first());
    }

    public function testAllowDisenchantForCraftAndEnchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->where('disposition', BatchCraftingDisposition::DISENCHANT->value)->first());
    }

    public function testRejectDisenchantForCraft(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectDisenchantForEnchant(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectDisenchantForAlchemy(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectDisenchantForHolyOils(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectDisenchantForTrinketry(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'shards' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testCancelActiveBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('batch-crafting.cancel', ['character' => $character]));

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->whereNotNull('cancelled_at')->first());
    }

    public function testCannotStartDuplicateActiveBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertSame(1, BatchCrafting::where('character_id', $character->id)->count());
    }

    public function testCannotStartWhileFactionLoyaltyIsRunning(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createCharacterAutomation(['character_id' => $character->id, 'type' => AutomationType::FACTION_LOYALTY]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testCanCoexistWithExplorationWhereExistingRulesAllowIt(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $this->createCharacterAutomation(['character_id' => $character->id, 'type' => AutomationType::EXPLORING]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNotNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectHolyOilsWithoutSelectedItems(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectHolyOilsWithoutSelectedOils(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [1],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectHolyOilsItemNotOwnedByCharacter(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectHolyOilsOilNotOwnedByCharacter(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $otherUser = $this->createUser();
        $otherCharacter = $this->createCharacter(['user_id' => $otherUser->id, 'name' => 'other-batch-crafter', 'inventory_max' => 10, 'gold_dust' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $otherAlchemyBag = $this->createAlchemyBag(['character_id' => $otherCharacter->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $otherAlchemyBag->id, 'character_id' => $otherCharacter->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectHolyOilsIneligibleItem(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'trinket', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }

    public function testRejectHolyOilsInvalidOilStack(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold_dust' => 100]);
        $inventory = $this->createInventory(['character_id' => $character->id]);
        $alchemyBag = $this->createAlchemyBag(['character_id' => $character->id]);
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'can_use_on_other_items' => false, 'holy_level' => null]);
        $this->createInventorySlot(['inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 1]);

        $this->actingAs($user)->post(route('batch-crafting.start', ['character' => $character]), [
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$item->id],
            'selected_oils' => [$oilSlot->id],
        ]);

        $this->assertNull(BatchCrafting::where('character_id', $character->id)->first());
    }
}
