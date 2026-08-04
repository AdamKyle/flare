<?php

namespace Tests\Unit\Game\Npcs\Actions\WorkBench\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class HolyItemServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?HolyItemService $holyItemService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->holyItemService = resolve(HolyItemService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->holyItemService = null;
    }

    public function test_fetch_smithing_items_returns_holy_oils_from_alchemy_bag_with_amount(): void
    {
        $equipment = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($equipment)->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 4,
        ]);

        $result = $this->holyItemService->fetchSmithingItems($character->refresh());

        $this->assertCount(1, $result['items']);
        $this->assertCount(1, $result['alchemy_items']);
        $this->assertEquals($slot->id, $result['alchemy_items']->first()->id);
        $this->assertEquals(4, $result['alchemy_items']->first()->amount);
    }

    public function test_fetch_smithing_items_includes_cost_lookup_keyed_by_slot_ids(): void
    {
        $equipment = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 2,
            'can_use_on_other_items' => true,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($equipment)->getCharacter();
        $itemSlot = $character->inventory->slots()->where('item_id', $equipment->id)->first();
        $alchemySlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->holyItemService->fetchSmithingItems($character->refresh());

        $this->assertEquals(4000, $result['costs'][$itemSlot->id][$alchemySlot->id]);
    }

    public function test_apply_holy_oil_decrements_alchemy_bag_stack(): void
    {
        $equipment = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($equipment)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $equipmentSlot = $character->inventory->slots()->where('item_id', $equipment->id)->first();
        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 2,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $equipmentSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(1, $oilSlot->refresh()->amount);
    }

    public function test_apply_holy_oil_deletes_alchemy_bag_stack_at_zero(): void
    {
        $equipment = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($equipment)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $equipmentSlot = $character->inventory->slots()->where('item_id', $equipment->id)->first();
        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $equipmentSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(0, AlchemyBagSlot::where('id', $oilSlot->id)->count());
    }

    public function test_apply_holy_oil_rejects_another_characters_alchemy_bag_slot(): void
    {
        $equipment = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($equipment)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $equipmentSlot = $character->inventory->slots()->where('item_id', $equipment->id)->first();
        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $oil->id,
            'amount' => 2,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $equipmentSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals(2, $oilSlot->refresh()->amount);
        $this->assertEquals(0, $equipment->refresh()->holy_stacks_applied);
    }

    public function test_apply_holy_oil_rejects_invalid_alchemy_item(): void
    {
        $equipment = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);
        $invalidOil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => false,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($equipment)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $equipmentSlot = $character->inventory->slots()->where('item_id', $equipment->id)->first();
        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $invalidOil->id,
            'amount' => 2,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $equipmentSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals(2, $oilSlot->refresh()->amount);
    }

    public function test_apply_holy_oil_successfully_applies_stack_and_updates_full_state(): void
    {
        Event::fake([CraftedItemTimeOutEvent::class]);

        $targetItem = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);

        $targetItem->appliedHolyStacks()->create([
            'item_id' => $targetItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $targetItem = $targetItem->refresh();

        $untouchedItem = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);

        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($targetItem)
            ->giveItem($untouchedItem)
            ->getCharacter();

        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $goldDustBeforeApply = $character->gold_dust;

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $untouchedSlot = $character->inventory->slots()->where('item_id', $untouchedItem->id)->first();

        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $expectedCost = $this->holyItemService->getCost($targetItem, $oil);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $targetSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals($goldDustBeforeApply - $expectedCost, $character->refresh()->gold_dust);
        $this->assertEquals(2, $targetItem->refresh()->holy_stacks_applied);
        $this->assertEquals($untouchedItem->id, $untouchedSlot->refresh()->item_id);
        $this->assertEquals(0, $untouchedItem->refresh()->holy_stacks_applied);
        $this->assertEquals(0, AlchemyBagSlot::where('id', $oilSlot->id)->count());
        Event::assertDispatched(CraftedItemTimeOutEvent::class);
    }

    public function test_apply_holy_oil_rejects_when_gold_dust_is_insufficient(): void
    {
        Event::fake([CraftedItemTimeOutEvent::class]);

        $targetItem = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $targetSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals(0, $character->refresh()->gold_dust);
        $this->assertEquals(0, $targetItem->refresh()->holy_stacks_applied);
        $this->assertEquals(1, $oilSlot->refresh()->amount);
        Event::assertNotDispatched(CraftedItemTimeOutEvent::class);
    }

    public function test_apply_holy_oil_rejects_when_max_holy_stacks_reached(): void
    {
        $targetItem = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 1,
        ]);

        $targetItem->appliedHolyStacks()->create([
            'item_id' => $targetItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $targetItem = $targetItem->refresh();

        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $goldDustBefore = $character->gold_dust;

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $targetSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals($goldDustBefore, $character->refresh()->gold_dust);
        $this->assertEquals(1, $targetItem->refresh()->holy_stacks_applied);
        $this->assertEquals(1, $oilSlot->refresh()->amount);
    }

    public function test_apply_holy_oil_rejects_trinket_target(): void
    {
        $trinketItem = $this->createItem([
            'type' => 'trinket',
            'holy_stacks' => 20,
        ]);
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($trinketItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $goldDustBefore = $character->gold_dust;

        $targetSlot = $character->inventory->slots()->where('item_id', $trinketItem->id)->first();
        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $targetSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals($goldDustBefore, $character->refresh()->gold_dust);
        $this->assertEquals(0, $trinketItem->refresh()->holy_stacks_applied);
        $this->assertEquals(1, $oilSlot->refresh()->amount);
    }

    public function test_apply_holy_oil_rejects_artifact_target(): void
    {
        $artifactItem = $this->createItem([
            'type' => 'artifact',
            'holy_stacks' => 20,
        ]);
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($artifactItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $goldDustBefore = $character->gold_dust;

        $targetSlot = $character->inventory->slots()->where('item_id', $artifactItem->id)->first();
        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $targetSlot->item_id,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals($goldDustBefore, $character->refresh()->gold_dust);
        $this->assertEquals(0, $artifactItem->refresh()->holy_stacks_applied);
        $this->assertEquals(1, $oilSlot->refresh()->amount);
    }

    public function test_apply_holy_oil_rejects_missing_target_slot(): void
    {
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $goldDustBefore = $character->gold_dust;

        $oilSlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->holyItemService->applyOil($character->refresh(), [
            'item_id' => $oil->id + 999999,
            'alchemy_slot_id' => $oilSlot->id,
        ]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals($goldDustBefore, $character->refresh()->gold_dust);
        $this->assertEquals(1, $oilSlot->refresh()->amount);
    }
}
