<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\CharacterInventoryServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class MultiInventoryActionCraftedSetDisenchantServiceTest extends TestCase
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

        $result = $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $set);

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

        $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $set);

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

        $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $set);

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

        $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $set);

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

        $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $set);

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

        $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $set);

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

        $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $set);

        $this->assertTrue($set->slots()->where('id', $trinketSlot->id)->exists());
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

        $result = $this->multiInventoryActionService->disenchantAllCraftedItemsSetSlots($character, $otherSet);

        $this->assertSame(422, $result['status']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }
}
