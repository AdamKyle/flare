<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Services\HolyOilSetPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class HolyOilSetPreviewServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?HolyOilSetPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->character->update(['gold_dust' => 5000]);
        $this->character = $this->character->refresh();

        $this->service = resolve(HolyOilSetPreviewService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_build_resolves_the_owned_set_and_its_real_contents_as_targets(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'name' => 'My Holy Set']);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $oilSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->id;

        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['inventory_set_id' => $set->id, 'oil_slot_ids' => [$oilSlotId], 'listing_price' => null],
        ]);

        $this->assertSame($set->id, $result['set_id']);
        $this->assertSame('My Holy Set', $result['set_name']);
        $this->assertSame(1, $result['target_count']);
        $this->assertSame(2, $result['planned_application_count']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_build_does_not_accept_client_supplied_target_item_ids(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $oilSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->id;

        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'inventory_set_id' => $set->id,
                'oil_slot_ids' => [$oilSlotId],
                'target_item_ids' => [999999],
                'listing_price' => null,
            ],
        ]);

        $this->assertSame(1, $result['target_count']);
        $this->assertSame($item->id, $result['targets'][0]['item_id']);
    }

    public function test_build_excludes_ineligible_set_items(): void
    {
        $trinket = $this->createItem(['type' => 'trinket', 'holy_stacks' => 5]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $trinket->id]);
        $oilSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->id;

        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['inventory_set_id' => $set->id, 'oil_slot_ids' => [$oilSlotId], 'listing_price' => null],
        ]);

        $this->assertSame(1, $result['target_count']);
        $this->assertFalse($result['targets'][0]['eligible']);
        $this->assertSame(0, $result['planned_application_count']);
    }

    public function test_build_reports_a_blocker_when_the_oil_slot_is_invalid(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);

        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['inventory_set_id' => $set->id, 'oil_slot_ids' => [999999], 'listing_price' => null],
        ]);

        $this->assertContains('None of the selected Holy Oils could be found.', $result['blockers']);
    }

    public function test_build_reports_a_blocker_when_the_character_has_no_alchemy_bag(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $this->character->alchemyBag->delete();
        $this->character = $this->character->refresh();

        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['inventory_set_id' => $set->id, 'oil_slot_ids' => [1], 'listing_price' => null],
        ]);

        $this->assertContains('None of the selected Holy Oils could be found.', $result['blockers']);
    }

    public function test_build_reports_a_blocker_when_gold_dust_is_insufficient(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $this->character->update(['gold_dust' => 0]);
        $this->character = $this->character->refresh();
        $oilSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->id;

        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['inventory_set_id' => $set->id, 'oil_slot_ids' => [$oilSlotId], 'listing_price' => null],
        ]);

        $this->assertContains('You do not have enough Gold Dust to apply the planned Holy Oils.', $result['blockers']);
    }

    public function test_build_returns_the_unavailable_set_preview_when_the_set_is_not_owned(): void
    {
        $result = $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['inventory_set_id' => 999999, 'oil_slot_ids' => [], 'listing_price' => null],
        ]);

        $this->assertNull($result['set_name']);
        $this->assertSame(0, $result['target_count']);
        $this->assertContains('The selected Inventory Set is no longer available.', $result['blockers']);
    }

    public function test_build_does_not_mutate_any_state(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $oilSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true])->id,
            'amount' => 5,
        ])->id;

        $this->service->build($this->character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['inventory_set_id' => $set->id, 'oil_slot_ids' => [$oilSlotId], 'listing_price' => null],
        ]);

        $this->assertSame(5000, $this->character->refresh()->gold_dust);
        $this->assertSame(1, $set->slots()->where('id', $setSlot->id)->count());
    }
}
