<?php

namespace Tests\Unit\Game\NpcActions\SeerActions\Services;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class HolyItemServiceTest extends TestCase {

    use RefreshDatabase, CreateItem;

    private ?CharacterFactory $character;

    private ?HolyItemService $holyItemService;

    public function setUp(): void {
        parent::setUp();

        $this->character       = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->holyItemService = resolve(HolyItemService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character       = null;
        $this->holyItemService = null;
    }

    public function fetchSmithingItems() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type'        => 'weapon',
                'holy_stacks' => 20,
            ])
        )->giveItem($this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]))->getCharacter();

        $result = $this->holyItemService->fetchSmithingItems($character);

        $this->assertNotEmpty($result['items']);
        $this->assertNotEmpty($result['alchemy_items']);
    }

    public function testCannotAffordToApplyOil() {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type'        => 'weapon',
                'holy_stacks' => 20,
            ])
        )->giveItem($this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]))->getCharacter();

        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'weapon';
        })->first();

        $alchemy = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'alchemy';
        })->first();

        $result = $this->holyItemService->applyOil($character, [
            'item_id' => $slot->item_id,
            'alchemy_item_id' => $alchemy->item_id,
        ]);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals(200, $result['status']);
    }

    public function testCannotApplyHolyOilWhenNoStacks() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type'        => 'weapon',
                'holy_stacks' => 0,
            ])
        )->giveItem($this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'weapon';
        })->first();

        $alchemy = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'alchemy';
        })->first();

        $result = $this->holyItemService->applyOil($character, [
            'item_id' => $slot->item_id,
            'alchemy_item_id' => $alchemy->item_id,
        ]);

        $this->assertEquals('Error: No stacks left.', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testApplyHolyOilToItem() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type'        => 'weapon',
                'holy_stacks' => 20,
            ])
        )->giveItem($this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'weapon';
        })->first();

        $alchemy = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'alchemy';
        })->first();

        $result = $this->holyItemService->applyOil($character, [
            'item_id' => $slot->item_id,
            'alchemy_item_id' => $alchemy->item_id,
        ]);

        $character = $character->refresh();

        $this->assertNotNull($character->inventory->slots->filter(function($slot) {
            return $slot->item->holy_stacks_applied === 1;
        })->first());

        $this->assertEquals(200, $result['status']);
    }

    public function testApplyHolyOilToItemWithOneStack() {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'type'        => 'weapon',
                'holy_stacks' => 1,
            ])
        )->giveItem($this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'weapon';
        })->first();

        $alchemy = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'alchemy';
        })->first();

        $result = $this->holyItemService->applyOil($character, [
            'item_id' => $slot->item_id,
            'alchemy_item_id' => $alchemy->item_id,
        ]);

        $character = $character->refresh();

        $this->assertNotNull($character->inventory->slots->filter(function($slot) {
            return $slot->item->holy_stacks_applied === 1;
        })->first());

        $this->assertEquals(200, $result['status']);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testApplyOilToItemWithStack() {
        $item = $this->createItem([
            'type'        => 'weapon',
            'holy_stacks' => 20,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                   => $item->id,
            'devouring_darkness_bonus'  => 0.10,
            'stat_increase_bonus'       => 0.10,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem(
            $item
        )->giveItem($this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'weapon';
        })->first();

        $alchemy = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'alchemy';
        })->first();

        $result = $this->holyItemService->applyOil($character, [
            'item_id' => $slot->item_id,
            'alchemy_item_id' => $alchemy->item_id,
        ]);

        $character = $character->refresh();

        $this->assertNotNull($character->inventory->slots->filter(function($slot) {
            return $slot->item->holy_stacks_applied === 2;
        })->first());

        $this->assertEquals(200, $result['status']);
    }


}
