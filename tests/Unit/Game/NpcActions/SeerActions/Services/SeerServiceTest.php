<?php

namespace Tests\Unit\Game\NpcActions\SeerActions\Services;

use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\NpcActions\SeerActions\Services\SeerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMap;

class SeerServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateGem, CreateMap;

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

        $this->createMap();

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

        $this->createMap();

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

        $this->createMap();

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
}
