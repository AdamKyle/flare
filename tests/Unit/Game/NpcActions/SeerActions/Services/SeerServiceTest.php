<?php

namespace Tests\Unit\Game\NpcActions\SeerActions\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\NpcActions\SeerActions\Services\SeerService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class SeerServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateGem, CreateGameMap;

    private ?CharacterFactory $character;

    private ?SeerService $seerService;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->seerService = resolve(SeerService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->seerService = null;
    }

    public function testItemToAddSocketsTooItemDoesNotExist() {
        $character = $this->character->getCharacter();
        $result = $this->seerService->createSockets($character, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item was found to apply sockets to.', $result['message']);
    }

    public function testCannotAffordToAttachSockets() {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'weapon',
        ]))->getCharacter();

        $slot = $character->inventory->slots->first();
        $result = $this->seerService->createSockets($character, $slot->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have the gold bars to do this.', $result['message']);
    }

    public function testCannotAddSocketsWhenYouHaveGemsAttached() {
        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 1,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $this->createGameMap();

        $character = $this->character->givePlayerLocation()
                                     ->inventoryManagement()
                                     ->giveItem($item->refresh())
                                     ->getCharacterFactory()
                                     ->kingdomManagement()
                                     ->assignKingdom([
                                         'gold_bars' => 2000,
                                     ])
                                     ->getCharacter();

        $slot   = $character->inventory->slots->first();
        $result = $this->seerService->createSockets($character, $slot->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot re-roll sockets as this item has gems attached. Remove them first.', $result['message']);
    }

    public function testAddSocketToItem() {
        $item = $this->createItem([
            'type' => 'weapon',
        ]);

        $this->createGameMap();

        $character = $this->character->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacterFactory()
            ->kingdomManagement()
            ->assignKingdom([
                'gold_bars' => 2000,
            ])
            ->getCharacter();

        $slot   = $character->inventory->slots->first();
        $result = $this->seerService->createSockets($character, $slot->id);

        $slot = $slot->refresh();

        $this->assertGreaterThan(0, $slot->item->socket_count);

        $this->assertEquals(200, $result['status']);
    }

    public function testAssignSocketsToItem() {

        $item = $this->createItem([
            'type' => 'weapon',
        ]);

        $this->createGameMap();

        $character = $this->character->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacterFactory()
            ->kingdomManagement()
            ->assignKingdom([
                'gold_bars' => 12000,
            ])
            ->getCharacter();

        forEach ([1 => 1, 50 => 2, 60 => 3, 80 => 4, 95 => 5, 100 => 6] as $percent => $socketCount) {
            $seerService = \Mockery::mock(SeerService::class)->makePartial();

            $seerService->shouldAllowMockingProtectedMethods()
                        ->shouldReceive('getRandomType')
                        ->andReturn($percent);

            $slot = $character->inventory->slots->first();
            $result = $seerService->createSockets($character, $slot->id);

            $slot = $slot->refresh();

            $this->assertEquals($socketCount, $slot->item->socket_count);

            $this->assertEquals(200, $result['status']);

            $character = $character->refresh();
        }
    }

    public function testGetItemsWithGemsForRemoval() {
        $item = $this->createItem([
            'type' => 'weapon',
        ]);

        $item->sockets()->create([
            'gem_id'  => $this->createGem()->id,
            'item_id' => $item->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item->refresh())->getCharacter();

        $result = $this->seerService->fetchGemsWithItemsForRemoval($character);

        $this->assertEquals(200, $result['status']);
        $this->assertNotEmpty($result['items']);
        $this->assertNotEmpty($result['gems']);
    }

    public function testCannotRemoveGemFromNonExistantItem() {
        $character = $this->character->getCharacter();
        $result = $this->seerService->removeGem($character, 10, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item was found to removed gem from.', $result['message']);
    }

    public function testCannotRemoveGemsFromItemWithNoSocketCount() {

        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->first();

        $result    = $this->seerService->removeGem($character, $slot->id, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No sockets to remove gem from.', $result['message']);
    }

    public function testCannotRemoveGemsFromItemWithNoGemsAttached() {
        $item = $this->createItem([
            'socket_count' => 5,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->first();

        $result    = $this->seerService->removeGem($character, $slot->id, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Sockets on this item are already empty.', $result['message']);
    }

    public function testCannotRemoveGemsFromItemWhenInventoryIsFull() {
        $item = $this->createItem([
            'socket_count' => 5,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item->refresh())->getCharacter();

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->first();

        $character->update([
            'inventory_max' => 0,
        ]);

        $result    = $this->seerService->removeGem($character, $slot->id, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Your inventory is full (gem bag counts).', $result['message']);
    }

    public function testCannotRemoveGemsFromItemCantAfford() {
        $item = $this->createItem([
            'socket_count' => 5,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item->refresh())->getCharacter();

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->first();

        $result    = $this->seerService->removeGem($character, $slot->id, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have the gold bars to do this.', $result['message']);
    }

    public function testCannotRemoveGemWhenGemIsNotOnTheItem() {
        $item = $this->createItem([
            'socket_count' => 5,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $character = $this->character->inventoryManagement()
                                     ->giveItem($item->refresh())
                                     ->getCharacterFactory()
                                     ->kingdomManagement()
                                     ->assignKingdom([
                                         'gold_bars' => 2000,
                                     ])
                                     ->getCharacter();

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->first();

        $result    = $this->seerService->removeGem($character, $slot->id, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Item does not have specified gem.', $result['message']);
    }

    public function testRemoveTheActualGem() {
        $item = $this->createItem([
            'socket_count' => 5,
        ]);

        $gem = $this->createGem();

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $gem->id,
        ]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item->refresh())
            ->getCharacterFactory()
            ->kingdomManagement()
            ->assignKingdom([
                'gold_bars' => 2000,
            ])
            ->getCharacter();

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->first();

        $result    = $this->seerService->removeGem($character, $slot->id, $gem->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Gem has been removed from the socket!', $result['message']);
    }

    public function testRemoveAllGemsFailsTheValidationTest() {
        $character = $this->character->getCharacter();
        $result   = $this->seerService->removeAllGems($character, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item was found to removed gem from.', $result['message']);
    }

    public function testNewInventoryCountAfterRemovingGemsWouldBeTooMuch() {
        $item      = $this->createItem([
            'socket_count' => 2
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item->refresh())->getCharacter();

        $character->update([
            'inventory_max' => 2,
        ]);

        $slot = $character->inventory->slots->where('item_id', $item->id)->first();

        $result    = $this->seerService->removeAllGems($character->refresh(), $slot->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Not enough room in your inventory to remove all the gems on this item. (gem bag counts).', $result['message']);
    }

    public function testDoesntHaveTheGoldBarsToRemoveAllGems() {
        $item      = $this->createItem([
            'socket_count' => 2
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $character = $this->character->inventoryManagement()
                                     ->giveItem($item->refresh())
                                     ->getCharacter();

        $slot = $character->inventory->slots->where('item_id', $item->id)->first();

        $result    = $this->seerService->removeAllGems($character, $slot->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You do not have the gold bars to do this.', $result['message']);
    }

    public function testRemoveAllGems() {
        $item      = $this->createItem([
            'socket_count' => 2,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id'  => $this->createGem()->id,
        ]);

        $character = $this->character->inventoryManagement()
                                     ->giveItem($item->refresh())
                                     ->getCharacterFactory()
                                     ->kingdomManagement()
                                     ->assignKingdom([
                                         'gold_bars' => 2000,
                                     ])
                                     ->getCharacter();

        $slot = $character->inventory->slots->where('item_id', $item->id)->first();

        $result    = $this->seerService->removeAllGems($character, $slot->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('All gems have been removed!', $result['message']);
    }
}
