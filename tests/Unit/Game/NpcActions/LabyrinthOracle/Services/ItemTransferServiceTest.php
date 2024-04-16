<?php

namespace Tests\Unit\Game\NpcActions\LabyrinthOracle\Services;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\LabyrinthOracle\Services\ItemTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemTransferServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateGem, CreateGameMap, CreateItemAffix;

    private ?CharacterFactory $character;

    private ?ItemTransferService $itemTransferService;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->itemTransferService = resolve(ItemTransferService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->itemTransferService = null;
    }

    public function testCannotAffordToTransfer() {
        $character = $this->character->getCharacter();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            10,
            10
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You cannot afford to do this.', $result['message']);
    }

    public function testItemsDoNotExistForTransfer() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            10,
            10
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have one of these items.', $result['message']);
    }

    public function testItemHasNothingToTransfer() {
        $itemToTransferFrom = $this->createItem();
        $itemToTransferTo = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo)
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('This item has nothing on it to transfer from.', $result['message']);
    }

    public function testNotEnoughInventoryWhenTheItemToMoveTooHasGemsAndYouDoNotHaveTheInventorySpace() {
        $itemToTransferFrom = $this->createItem();
        $itemToTransferTo = $this->createItem();

        $itemToTransferFrom->sockets()->create([
            'gem_id' => $this->createGem()->id,
            'item_id' => $itemToTransferTo->id,
        ]);

        $itemToTransferFrom->update([
            'socket_count' => 1
        ]);

        $itemToTransferTo->sockets()->create([
            'gem_id' => $this->createGem()->id,
            'item_id' => $itemToTransferTo->id,
        ]);

        $itemToTransferTo->update([
            'socket_count' => 1
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();
        $itemToTransferTo = $itemToTransferTo->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo)
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have the inventory room to move the gems attached to: ' . $itemToTransferTo->affix_name . ' back into your gem bag.', $result['message']);
    }

    public function testTransferItemAttributes() {

        Event::fake();

        $attachedSuffix = $this->createItemAffix([
            'type' => 'suffix'
        ]);

        $attachedPrefix = $this->createItemAffix([
            'type' => 'prefix'
        ]);

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $attachedSuffix->id,
            'item_prefix_id' => $attachedPrefix->id,
            'socket_count' => 2,
            'holy_stacks'  => 1,
        ]);

        $itemToTransferFrom->appliedHolyStacks()->create([
            'item_id' => $itemToTransferFrom->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();

        $gemToAttach = $this->createGem();

        $itemToTransferFrom->sockets()->create([
            'item_id' => $itemToTransferFrom->id,
            'gem_id' => $gemToAttach->id,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();
        $itemToTransferTo   = $this->createItem([
            'holy_stacks' => 1,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo);

        $slotForItemToTransferTo = $character->getSlotId(1);

        $character = $character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $character = $character->refresh();

        $transferredToItem = $character->inventory->slots->where('id', $slotForItemToTransferTo)->first()->item;

        $this->assertEquals(200, $result['status']);
        $this->assertGreaterThan(0, $result['inventory']);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals($transferredToItem->item_suffix_id, $attachedSuffix->id);
        $this->assertEquals($transferredToItem->item_prefix_id, $attachedPrefix->id);
        $this->assertEquals($transferredToItem->holy_stacks_applied, 1);
        $expectedAttributes = [
            'item_id' => $transferredToItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ];

        $actualAttributes = $transferredToItem->appliedHolyStacks->first()->only(array_keys($expectedAttributes));

        $this->assertEquals($actualAttributes, $expectedAttributes);
        $this->assertEquals($transferredToItem->socket_count, 2);
        $this->assertEquals($transferredToItem->sockets->first()->gem_id, $gemToAttach->id);
    }

    public function testTransferItemAttributesWhenOneIsMythic() {

        Event::fake();

        $attachedSuffix = $this->createItemAffix([
            'type' => 'suffix'
        ]);

        $attachedPrefix = $this->createItemAffix([
            'type' => 'prefix'
        ]);

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $attachedSuffix->id,
            'item_prefix_id' => $attachedPrefix->id,
            'socket_count' => 2,
            'holy_stacks'  => 1,
            'is_mythic' => true,
        ]);

        $itemToTransferFrom->appliedHolyStacks()->create([
            'item_id' => $itemToTransferFrom->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();

        $gemToAttach = $this->createGem();

        $itemToTransferFrom->sockets()->create([
            'item_id' => $itemToTransferFrom->id,
            'gem_id' => $gemToAttach->id,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();
        $itemToTransferTo   = $this->createItem([
            'holy_stacks' => 1,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo);

        $slotForItemToTransferTo = $character->getSlotId(1);

        $character = $character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $character = $character->refresh();

        $transferredToItem = $character->inventory->slots->where('id', $slotForItemToTransferTo)->first()->item;

        $this->assertEquals(200, $result['status']);
        $this->assertGreaterThan(0, $result['inventory']);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals($transferredToItem->item_suffix_id, $attachedSuffix->id);
        $this->assertEquals($transferredToItem->item_prefix_id, $attachedPrefix->id);
        $this->assertEquals($transferredToItem->holy_stacks_applied, 1);
        $this->assertTrue($transferredToItem->is_mythic);
        $expectedAttributes = [
            'item_id' => $transferredToItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ];

        $actualAttributes = $transferredToItem->appliedHolyStacks->first()->only(array_keys($expectedAttributes));

        $this->assertEquals($actualAttributes, $expectedAttributes);
        $this->assertEquals($transferredToItem->socket_count, 2);
        $this->assertEquals($transferredToItem->sockets->first()->gem_id, $gemToAttach->id);
    }

    public function testTransferItemAttributesWhenOneCosmic() {

        Event::fake();

        $attachedSuffix = $this->createItemAffix([
            'type' => 'suffix'
        ]);

        $attachedPrefix = $this->createItemAffix([
            'type' => 'prefix'
        ]);

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $attachedSuffix->id,
            'item_prefix_id' => $attachedPrefix->id,
            'socket_count' => 2,
            'holy_stacks'  => 1,
            'is_cosmic' => true,
        ]);

        $itemToTransferFrom->appliedHolyStacks()->create([
            'item_id' => $itemToTransferFrom->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();

        $gemToAttach = $this->createGem();

        $itemToTransferFrom->sockets()->create([
            'item_id' => $itemToTransferFrom->id,
            'gem_id' => $gemToAttach->id,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();
        $itemToTransferTo   = $this->createItem([
            'holy_stacks' => 1,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo);

        $slotForItemToTransferTo = $character->getSlotId(1);

        $character = $character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $character = $character->refresh();

        $transferredToItem = $character->inventory->slots->where('id', $slotForItemToTransferTo)->first()->item;

        $this->assertEquals(200, $result['status']);
        $this->assertGreaterThan(0, $result['inventory']);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals($transferredToItem->item_suffix_id, $attachedSuffix->id);
        $this->assertEquals($transferredToItem->item_prefix_id, $attachedPrefix->id);
        $this->assertEquals($transferredToItem->holy_stacks_applied, 1);
        $this->assertTrue($transferredToItem->is_cosmic);
        $expectedAttributes = [
            'item_id' => $transferredToItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ];

        $actualAttributes = $transferredToItem->appliedHolyStacks->first()->only(array_keys($expectedAttributes));

        $this->assertEquals($actualAttributes, $expectedAttributes);
        $this->assertEquals($transferredToItem->socket_count, 2);
        $this->assertEquals($transferredToItem->sockets->first()->gem_id, $gemToAttach->id);
    }

    public function testTransferItemAttributesWithGemsBeingReturned() {

        Event::fake();

        $attachedSuffix = $this->createItemAffix([
            'type' => 'suffix'
        ]);

        $attachedPrefix = $this->createItemAffix([
            'type' => 'prefix'
        ]);

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $attachedSuffix->id,
            'item_prefix_id' => $attachedPrefix->id,
            'socket_count' => 2,
            'holy_stacks' => 1,
        ]);

        $itemToTransferFrom->appliedHolyStacks()->create([
            'item_id' => $itemToTransferFrom->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();

        $gemToAttach = $this->createGem();

        $itemToTransferFrom->sockets()->create([
            'item_id' => $itemToTransferFrom->id,
            'gem_id' => $gemToAttach->id,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();
        $itemToTransferTo   = $this->createItem([
            'holy_stacks' => 1,
        ]);

        $gemToRemove = $this->createGem();

        $itemToTransferTo->sockets()->create([
            'item_id' => $itemToTransferTo->id,
            'gem_id' => $gemToRemove->id,
        ]);

        $itemToTransferTo->update([
            'socket_count' => 1,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo);

        $slotForItemToTransferTo = $character->getSlotId(1);

        $character = $character->getCharacterFactory()
            ->gemBagManagement()
            ->assignGemToBag($gemToRemove->id)
            ->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $character = $character->refresh();

        $transferredToItem = $character->inventory->slots->where('id', $slotForItemToTransferTo)->first()->item;

        $this->assertEquals(200, $result['status']);
        $this->assertGreaterThan(0, $result['inventory']);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals($transferredToItem->item_suffix_id, $attachedSuffix->id);
        $this->assertEquals($transferredToItem->item_prefix_id, $attachedPrefix->id);
        $this->assertEquals($transferredToItem->holy_stacks_applied, 1);
        $expectedAttributes = [
            'item_id' => $transferredToItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ];

        $actualAttributes = $transferredToItem->appliedHolyStacks->first()->only(array_keys($expectedAttributes));

        $this->assertEquals($actualAttributes, $expectedAttributes);
        $this->assertEquals($transferredToItem->socket_count, 2);
        $this->assertEquals($transferredToItem->sockets->first()->gem_id, $gemToAttach->id);

        $this->assertEquals(2, $character->gemBag->gemSlots()->where('gem_id', $gemToRemove->id)->first()->amount);
    }

    public function testTransferItemAttributesWithGemsBeingReturnedAsNewEntries() {

        Event::fake();

        $attachedSuffix = $this->createItemAffix([
            'type' => 'suffix'
        ]);

        $attachedPrefix = $this->createItemAffix([
            'type' => 'prefix'
        ]);

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $attachedSuffix->id,
            'item_prefix_id' => $attachedPrefix->id,
            'socket_count' => 2,
            'holy_stacks' => 1,
        ]);

        $itemToTransferFrom->appliedHolyStacks()->create([
            'item_id' => $itemToTransferFrom->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();

        $gemToAttach = $this->createGem();

        $itemToTransferFrom->sockets()->create([
            'item_id' => $itemToTransferFrom->id,
            'gem_id' => $gemToAttach->id,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();
        $itemToTransferTo   = $this->createItem([
            'holy_stacks' => 1,
        ]);

        $gemToRemove = $this->createGem();

        $itemToTransferTo->sockets()->create([
            'item_id' => $itemToTransferTo->id,
            'gem_id' => $gemToRemove->id,
        ]);

        $itemToTransferTo->update([
            'socket_count' => 1,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo);

        $slotForItemToTransferTo = $character->getSlotId(1);

        $character = $character->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $character = $character->refresh();

        $transferredToItem = $character->inventory->slots->where('id', $slotForItemToTransferTo)->first()->item;

        $this->assertEquals(200, $result['status']);
        $this->assertGreaterThan(0, $result['inventory']);

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals($transferredToItem->item_suffix_id, $attachedSuffix->id);
        $this->assertEquals($transferredToItem->item_prefix_id, $attachedPrefix->id);
        $this->assertEquals($transferredToItem->holy_stacks_applied, 1);
        $expectedAttributes = [
            'item_id' => $transferredToItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ];

        $actualAttributes = $transferredToItem->appliedHolyStacks->first()->only(array_keys($expectedAttributes));

        $this->assertEquals($actualAttributes, $expectedAttributes);
        $this->assertEquals($transferredToItem->socket_count, 2);
        $this->assertEquals($transferredToItem->sockets->first()->gem_id, $gemToAttach->id);

        $this->assertEquals(1, $character->gemBag->gemSlots()->where('gem_id', $gemToRemove->id)->first()->amount);
    }
}
