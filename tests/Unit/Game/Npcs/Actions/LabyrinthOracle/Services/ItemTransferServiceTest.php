<?php

namespace Tests\Unit\Game\Npcs\Actions\LabyrinthOracle\Services;

use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Npcs\Actions\LabyrinthOracle\Services\ItemTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemTransferServiceTest extends TestCase
{
    use CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ItemTransferService $itemTransferService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->itemTransferService = resolve(ItemTransferService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->itemTransferService = null;
    }

    public function test_missing_source_rejects()
    {
        $itemToTransferTo = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferTo)
            ->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferTo->id + 1_000_000,
            $itemToTransferTo->id,
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have one of these items.', $result['message']);
    }

    public function test_valid_source_with_missing_destination_rejects()
    {
        $itemToTransferFrom = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferFrom->id + 1_000_000,
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have one of these items.', $result['message']);
    }

    public function test_same_source_and_destination_rejects()
    {
        $item = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $item->id,
            $item->id,
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You cannot transfer attributes to the same item.', $result['message']);
    }

    public function test_insufficient_currency_changes_no_items_or_currencies()
    {
        $attachedSuffix = $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $attachedSuffix->id,
        ]);
        $itemToTransferTo = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo)
            ->getCharacter();

        $originalGold = $character->gold;
        $originalShards = $character->shards;
        $originalGoldDust = $character->gold_dust;

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You cannot afford to do this.', $result['message']);
        $this->assertEquals($originalGold, $character->gold);
        $this->assertEquals($originalShards, $character->shards);
        $this->assertEquals($originalGoldDust, $character->gold_dust);
        $this->assertEquals(
            $itemToTransferFrom->id,
            $character->inventory->slots->where('item_id', $itemToTransferFrom->id)->first()->item_id
        );
        $this->assertEquals($attachedSuffix->id, $itemToTransferFrom->refresh()->item_suffix_id);
        $this->assertNull($itemToTransferTo->refresh()->item_suffix_id);
    }

    public function test_successful_transfer_deducts_exact_gold_and_shards_and_gold_dust()
    {
        Event::fake();

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
            ])->id,
        ]);
        $itemToTransferTo = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo)
            ->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(CurrencyLimit::MAX_GOLD - 100_000_000, $character->gold);
        $this->assertEquals(CurrencyLimit::MAX_SHARDS - 5_000, $character->shards);
        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST - 2_500, $character->gold_dust);
    }

    public function test_successful_transfer_moves_affixes()
    {
        Event::fake();

        $attachedSuffix = $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $attachedPrefix = $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $attachedSuffix->id,
            'item_prefix_id' => $attachedPrefix->id,
        ]);
        $itemToTransferTo = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo);

        $slotForItemToTransferTo = $character->getSlotId(1);

        $character = $character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
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
        $this->assertEquals($attachedSuffix->id, $transferredToItem->item_suffix_id);
        $this->assertEquals($attachedPrefix->id, $transferredToItem->item_prefix_id);
    }

    public function test_successful_transfer_moves_holy_oil_stacks()
    {
        Event::fake();

        $itemToTransferFrom = $this->createItem([
            'holy_stacks' => 1,
        ]);

        $itemToTransferFrom->appliedHolyStacks()->create([
            'item_id' => $itemToTransferFrom->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();

        $itemToTransferTo = $this->createItem([
            'holy_stacks' => 1,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo);

        $slotForItemToTransferTo = $character->getSlotId(1);

        $character = $character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
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
        $this->assertEquals(1, $transferredToItem->holy_stacks_applied);

        $expectedAttributes = [
            'item_id' => $transferredToItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ];

        $actualAttributes = $transferredToItem->appliedHolyStacks->first()->only(array_keys($expectedAttributes));

        $this->assertEquals($expectedAttributes, $actualAttributes);
    }

    public function test_successful_transfer_moves_gems()
    {
        Event::fake();

        $itemToTransferFrom = $this->createItem([
            'socket_count' => 1,
        ]);

        $gemToAttach = $this->createGem();

        $itemToTransferFrom->sockets()->create([
            'item_id' => $itemToTransferFrom->id,
            'gem_id' => $gemToAttach->id,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();

        $itemToTransferTo = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo);

        $slotForItemToTransferTo = $character->getSlotId(1);

        $character = $character->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
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
        $this->assertEquals(1, $transferredToItem->socket_count);
        $this->assertEquals($gemToAttach->id, $transferredToItem->sockets->first()->gem_id);
    }

    public function test_destination_gems_require_gem_bag_capacity_rejects_when_insufficient_capacity()
    {
        $itemToTransferFrom = $this->createItem();
        $itemToTransferTo = $this->createItem();

        $itemToTransferFrom->sockets()->create([
            'gem_id' => $this->createGem()->id,
            'item_id' => $itemToTransferFrom->id,
        ]);

        $itemToTransferFrom->update([
            'socket_count' => 1,
        ]);

        $itemToTransferTo->sockets()->create([
            'gem_id' => $this->createGem()->id,
            'item_id' => $itemToTransferTo->id,
        ]);

        $itemToTransferTo->update([
            'socket_count' => 1,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();
        $itemToTransferTo = $itemToTransferTo->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo)
            ->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'gem_bag_limit' => 0,
        ]);

        $character = $character->refresh();

        $result = $this->itemTransferService->transferItemEnhancements(
            $character,
            $itemToTransferFrom->id,
            $itemToTransferTo->id,
        );

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have room in your Gem Bag to move the gems attached to: '.$itemToTransferTo->affix_name.'.', $result['message']);
    }
}
