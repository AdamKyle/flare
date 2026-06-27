<?php

namespace Tests\Unit\Game\NpcActions\WorkBench\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $character->update(['gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST]);
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
        $character->update(['gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST]);
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
        $character->update(['gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST]);
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
        $character->update(['gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST]);
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
}
