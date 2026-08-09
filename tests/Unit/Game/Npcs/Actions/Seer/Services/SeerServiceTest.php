<?php

namespace Tests\Unit\Game\Npcs\Actions\Seer\Services;

use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Npcs\Actions\Seer\Services\SeerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class SeerServiceTest extends TestCase
{
    use CreateGem, CreateItem, MockeryPHPUnitIntegration, RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?SeerService $seerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->seerService = resolve(SeerService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->seerService = null;
    }

    public function test_fetch_paginated_gems_returns_empty_response_when_character_has_no_gem_bag(): void
    {
        $character = $this->character->getCharacter();
        $character->gemBag()->delete();

        $result = $this->seerService->fetchPaginatedGems($character->refresh(), 10, 1);

        $this->assertSame([], $result['data']);
    }

    public function test_get_items_when_managing_gems_only_returns_items_with_sockets(): void
    {
        $withSockets = $this->createItem(['type' => 'weapon', 'socket_count' => 2]);
        $withoutSockets = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);

        $character = $this->character->inventoryManagement()
            ->giveItem($withSockets)
            ->giveItem($withoutSockets)
            ->getCharacter();

        $result = $this->seerService->getItems($character, true);

        $this->assertCount(1, $result);
    }

    public function test_create_sockets_returns_error_when_slot_not_found(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->seerService->createSockets($character, 999999);

        $this->assertSame('No item was found to apply sockets to.', $result['message']);
    }

    public function test_create_sockets_returns_error_for_trinket_type(): void
    {
        $trinket = $this->createItem(['type' => 'trinket', 'socket_count' => 0]);
        $character = $this->character->inventoryManagement()->giveItem($trinket)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $trinket->id)->first();

        $result = $this->seerService->createSockets($character, $slot->id);

        $this->assertSame('Trinkets and Artifacts cannot have sockets on them.', $result['message']);
    }

    public function test_create_sockets_returns_error_when_not_enough_gold_bars(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->createSockets($character, $slot->id);

        $this->assertSame('You do not have the gold bars to do this.', $result['message']);
    }

    public function test_create_sockets_returns_failure_message_when_item_already_has_max_sockets(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 6]);
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->createSockets($character, $slot->id);

        $this->assertStringContainsString('Failed to attach new sockets', $result['message']);
    }

    public function test_remove_gem_returns_error_when_slot_not_found(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->seerService->removeGem($character, 999999, 1);

        $this->assertSame('No item was found to removed gem from.', $result['message']);
    }

    public function test_remove_gem_returns_error_when_item_has_no_sockets(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeGem($character, $slot->id, 1);

        $this->assertSame('No sockets to remove gem from.', $result['message']);
    }

    public function test_remove_gem_returns_error_when_sockets_are_empty(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 2]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeGem($character, $slot->id, 1);

        $this->assertSame('Sockets on this item are already empty.', $result['message']);
    }

    public function test_remove_gem_returns_error_when_gem_bag_is_full(): void
    {
        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $gem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character->update(['gem_bag_limit' => 0]);
        $slot = $character->refresh()->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeGem($character, $slot->id, $gem->id);

        $this->assertSame('Your Gem Bag is full.', $result['message']);
    }

    public function test_remove_gem_returns_error_when_not_enough_gold_bars(): void
    {
        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $gem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeGem($character, $slot->id, $gem->id);

        $this->assertSame('You do not have the gold bars to do this.', $result['message']);
    }

    public function test_remove_gem_returns_error_when_gem_id_does_not_match_item(): void
    {
        $gem = $this->createGem();
        $otherGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $gem->id]);

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeGem($character, $slot->id, $otherGem->id);

        $this->assertSame('Item does not have specified gem.', $result['message']);
    }

    public function test_remove_gem_successfully_removes_the_gem_from_the_item(): void
    {
        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $gem->id]);

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeGem($character, $slot->id, $gem->id);

        $this->assertSame('Gem has been removed from the socket!', $result['message']);
    }

    public function test_remove_all_gems_returns_error_when_not_enough_gem_bag_room(): void
    {
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 2]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $firstGem->id]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $secondGem->id]);

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character->update(['gem_bag_limit' => 1]);
        $slot = $character->refresh()->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeAllGems($character, $slot->id);

        $this->assertSame('Not enough room in your Gem Bag to remove all the gems on this item.', $result['message']);
    }

    public function test_remove_all_gems_returns_error_when_not_enough_gold_bars(): void
    {
        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $gem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeAllGems($character, $slot->id);

        $this->assertSame('You do not have the gold bars to do this.', $result['message']);
    }

    public function test_remove_all_gems_successfully_removes_every_gem(): void
    {
        $firstGem = $this->createGem();
        $secondGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 2]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $firstGem->id]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $secondGem->id]);

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeAllGems($character, $slot->id);

        $this->assertSame('All gems have been removed!', $result['message']);
    }

    public function test_replace_gem_returns_error_when_slot_not_found(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->seerService->replaceGem($character, 999999, 1, 1);

        $this->assertSame('No item was found to replace gem on.', $result['message']);
    }

    public function test_replace_gem_returns_error_when_gem_slot_not_found(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->replaceGem($character, $slot->id, 999999, 1);

        $this->assertSame('The gem you want to use to replace the requested gem with, does not exist.', $result['message']);
    }

    public function test_replace_gem_returns_error_when_item_has_no_sockets(): void
    {
        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($gem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->replaceGem($character, $slot->id, $gemSlot->id, $gem->id);

        $this->assertSame('The item does not have any sockets. What are you doing?', $result['message']);
    }

    public function test_replace_gem_returns_error_when_gem_bag_is_full(): void
    {
        $existingGem = $this->createGem();
        $replacementGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $existingGem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($replacementGem->id)->getCharacter();
        $character->update(['gem_bag_limit' => 0]);
        $character = $character->refresh();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->replaceGem($character, $slot->id, $gemSlot->id, $existingGem->id);

        $this->assertSame('Your Gem Bag is full. Could not replace the gem.', $result['message']);
    }

    public function test_replace_gem_returns_error_when_not_enough_gold_bars(): void
    {
        $existingGem = $this->createGem();
        $replacementGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $existingGem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($replacementGem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->replaceGem($character, $slot->id, $gemSlot->id, $existingGem->id);

        $this->assertSame('You do not have the gold bars to do this.', $result['message']);
    }

    public function test_replace_gem_returns_error_when_gem_to_replace_not_found_on_item(): void
    {
        $existingGem = $this->createGem();
        $unrelatedGem = $this->createGem();
        $replacementGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $existingGem->id]);

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($replacementGem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->replaceGem($character, $slot->id, $gemSlot->id, $unrelatedGem->id);

        $this->assertSame('No Gem found on the item for the gem you want to replace.', $result['message']);
    }

    public function test_replace_gem_successfully_replaces_the_gem(): void
    {
        $existingGem = $this->createGem();
        $replacementGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $existingGem->id]);

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($replacementGem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->replaceGem($character, $slot->id, $gemSlot->id, $existingGem->id);

        $this->assertSame('Gem has been replaced!', $result['message']);
    }

    public function test_assign_gem_to_socket_returns_error_when_slot_not_found(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->seerService->assignGemToSocket($character, 999999, 1);

        $this->assertSame('No item was found to add a gem to.', $result['message']);
    }

    public function test_assign_gem_to_socket_returns_error_when_gem_slot_not_found(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->assignGemToSocket($character, $slot->id, 999999);

        $this->assertSame('No gem to attach to supplied item was found.', $result['message']);
    }

    public function test_assign_gem_to_socket_returns_error_when_item_has_no_sockets(): void
    {
        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($gem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->assignGemToSocket($character, $slot->id, $gemSlot->id);

        $this->assertSame('No Sockets on the supplied item. You need to add sockets to the item first.', $result['message']);
    }

    public function test_assign_gem_to_socket_returns_error_when_all_sockets_are_full(): void
    {
        $existingGem = $this->createGem();
        $newGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $item->sockets()->create(['item_id' => $item->id, 'gem_id' => $existingGem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($newGem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->assignGemToSocket($character, $slot->id, $gemSlot->id);

        $this->assertSame('Not enough sockets for this gem.', $result['message']);
    }

    public function test_assign_gem_to_socket_returns_error_when_not_enough_gold_bars(): void
    {
        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($gem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->assignGemToSocket($character, $slot->id, $gemSlot->id);

        $this->assertSame('You do not have the gold bars to do this.', $result['message']);
    }

    public function test_assign_gem_to_socket_successfully_attaches_the_gem(): void
    {
        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($gem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $result = $this->seerService->assignGemToSocket($character, $slot->id, $gemSlot->id);

        $this->assertSame('Attached gem to item!', $result['message']);
    }

    public function test_remove_all_gems_returns_the_validation_failure_when_item_has_no_sockets(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $result = $this->seerService->removeAllGems($character, $slot->id);

        $this->assertSame('No sockets to remove gem from.', $result['message']);
    }

    public function test_create_sockets_assigns_six_sockets_when_roll_is_at_the_top_tier(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock) {
                $mock->shouldReceive('numberBetween')->with(1, 100)->once()->andReturn(100);
            })
        );

        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        resolve(SeerService::class)->createSockets($character, $slot->id);

        $this->assertSame(6, $slot->refresh()->item->socket_count);
    }

    public function test_create_sockets_assigns_five_sockets_when_roll_is_in_the_upper_tier(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock) {
                $mock->shouldReceive('numberBetween')->with(1, 100)->once()->andReturn(95);
            })
        );

        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        resolve(SeerService::class)->createSockets($character, $slot->id);

        $this->assertSame(5, $slot->refresh()->item->socket_count);
    }

    public function test_create_sockets_assigns_three_sockets_when_roll_is_in_the_mid_tier(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock) {
                $mock->shouldReceive('numberBetween')->with(1, 100)->once()->andReturn(60);
            })
        );

        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        resolve(SeerService::class)->createSockets($character, $slot->id);

        $this->assertSame(3, $slot->refresh()->item->socket_count);
    }

    public function test_create_sockets_assigns_one_socket_when_roll_is_at_the_bottom_tier(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock) {
                $mock->shouldReceive('numberBetween')->with(1, 100)->once()->andReturn(1);
            })
        );

        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        resolve(SeerService::class)->createSockets($character, $slot->id);

        $this->assertSame(1, $slot->refresh()->item->socket_count);
    }
}
