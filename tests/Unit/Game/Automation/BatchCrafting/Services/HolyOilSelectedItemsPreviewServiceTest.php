<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Services\HolyOilSelectedItemsPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateItem;

class HolyOilSelectedItemsPreviewServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateItem, RefreshDatabase;

    private ?HolyOilSelectedItemsPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(HolyOilSelectedItemsPreviewService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_build_resolves_selected_targets_and_oil_pool_and_returns_the_plan(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => 5000]);
        $character = $character->refresh();
        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 5,
        ]);

        $result = $this->service->build($character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['target_slot_ids' => [$targetSlot->id], 'oil_slot_ids' => [$oilSlot->id], 'listing_price' => null],
        ]);

        $this->assertSame(1, $result['target_count']);
        $this->assertSame(5, $result['oil_units_available']);
        $this->assertSame(2, $result['planned_application_count']);
        $this->assertSame($character->gold_dust, $result['gold_dust_available']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_build_does_not_mutate_any_state(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => 5000]);
        $character = $character->refresh();
        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 5,
        ]);

        $this->service->build($character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['target_slot_ids' => [$targetSlot->id], 'oil_slot_ids' => [$oilSlot->id], 'listing_price' => null],
        ]);

        $this->assertSame(5000, $character->refresh()->gold_dust);
        $this->assertSame(5, $oilSlot->refresh()->amount);
        $this->assertSame(1, $character->inventory->slots()->where('id', $targetSlot->id)->count());
    }

    public function test_build_reports_a_blocker_when_the_target_slot_is_invalid(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->build($character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['target_slot_ids' => [999999], 'oil_slot_ids' => [], 'listing_price' => null],
        ]);

        $this->assertContains('None of the selected target items could be found.', $result['blockers']);
    }

    public function test_build_reports_a_blocker_when_the_character_has_no_inventory(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->inventory->delete();
        $character = $character->refresh();

        $result = $this->service->build($character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['target_slot_ids' => [1], 'oil_slot_ids' => [], 'listing_price' => null],
        ]);

        $this->assertContains('None of the selected target items could be found.', $result['blockers']);
    }

    public function test_build_reports_a_blocker_when_the_character_has_no_alchemy_bag(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $character->alchemyBag->delete();
        $character = $character->refresh();

        $result = $this->service->build($character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['target_slot_ids' => [$targetSlot->id], 'oil_slot_ids' => [1], 'listing_price' => null],
        ]);

        $this->assertContains('None of the selected Holy Oils could be found.', $result['blockers']);
    }

    public function test_build_reports_a_blocker_when_the_oil_slot_is_invalid(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $result = $this->service->build($character->refresh(), [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['target_slot_ids' => [$targetSlot->id], 'oil_slot_ids' => [999999], 'listing_price' => null],
        ]);

        $this->assertContains('None of the selected Holy Oils could be found.', $result['blockers']);
    }

    public function test_build_reports_a_blocker_when_gold_dust_is_insufficient_for_the_planned_applications(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character = $character->refresh();
        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 5, 'can_use_on_other_items' => true]);
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 5,
        ]);

        $result = $this->service->build($character, [
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['target_slot_ids' => [$targetSlot->id], 'oil_slot_ids' => [$oilSlot->id], 'listing_price' => null],
        ]);

        $this->assertContains('You do not have enough Gold Dust to apply the planned Holy Oils.', $result['blockers']);
    }
}
