<?php

namespace Tests\Feature\Game\NpcActions\SeerActions\Controllers\Api;

use App\Flare\Values\WeaponTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class SeerCampControllerTest extends TestCase
{
    use CreateGameMap, CreateGem, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testVisitCamp()
    {

        $gemForAddition = $this->createGem();

        $item = $this->createItem(['type' => WeaponTypes::BOW, 'socket_count' => 2]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $character->gemBag()->create([
            'character_id' => $character->id,
        ]);

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $gemForAddition->id,
            'amount' => 1,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/visit-seer-camp/' . $character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['items']);
        $this->assertCount(1, $jsonData['gems']);
    }

    public function testRollSockets()
    {
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

        $slot = $character->inventory->slots->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/add-sockets/' . $character->id, [
                '_token' => csrf_token(),
                'slot_id' => $slot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $slot = $slot->refresh();

        $this->assertEquals('Attached sockets to item! (Old Socket Count: ' . 0 . ', New Count: ' . $slot->item->socket_count . ').', $jsonData['message']);
    }

    public function testAttachGemToItem()
    {
        $item = $this->createItem([
            'socket_count' => 1,
        ]);

        $gemForAddition = $this->createGem();

        $character = $this->character->inventoryManagement()
            ->giveItem($item->refresh())
            ->getCharacterFactory()
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 2000])
            ->getCharacter();

        $character->gemBag()->create([
            'character_id' => $character->id,
        ]);

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $gemForAddition->id,
            'amount' => 1,
        ]);

        $slot = $character->inventory->slots->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->where('gem_id', $gemForAddition->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/add-gem/' . $character->id, [
                '_token' => csrf_token(),
                'slot_id' => $slot->id,
                'gem_slot_id' => $gemSlot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Attached gem to item!', $jsonData['message']);
    }

    public function testReplaceGemOnItem()
    {
        $gemForItem = $this->createGem();

        $item = $this->createItem([
            'socket_count' => 2,
        ]);

        $item->sockets()->create([
            'gem_id' => $gemForItem->id,
        ]);

        $gem = $this->createGem();

        $character = $this->character->inventoryManagement()
            ->giveItem($item->refresh())
            ->getCharacterFactory()
            ->kingdomManagement()
            ->assignKingdom([
                'gold_bars' => 2000,
            ])
            ->getCharacter();

        $character->gemBag()->create([
            'character_id' => $character->id,
        ]);

        $character = $character->refresh();

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gembag->id,
            'gem_id' => $gem->id,
            'amount' => 1,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->where('gem_id', $gem->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/replace-gem/' . $character->id, [
                '_token' => csrf_token(),
                'slot_id' => $slot->id,
                'gem_slot_id' => $gemSlot->id,
                'gem_slot_to_replace' => $item->sockets->first()->gem_id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Gem has been replaced!', $jsonData['message']);
    }

    public function testFetchItemsWithGems()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 2,
        ]);

        $item->sockets()->create([
            'gem_id' => $this->createGem()->id,
            'item_id' => $item->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item->refresh())->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/gems-to-remove/' . $character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['items']);
        $this->assertCount(1, $jsonData['gems']);
    }

    public function testRemoveGemFromItem()
    {
        $item = $this->createItem([
            'name' => 'Item Name',
            'socket_count' => 5,
        ]);

        $secondItemWithNoSocketCount = $this->createItem([
            'name' => 'Rage Face 101',
        ]);

        $gem = $this->createGem();

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $gem->id,
        ]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item->refresh())
            ->giveItem($secondItemWithNoSocketCount)
            ->getCharacterFactory()
            ->kingdomManagement()
            ->assignKingdom([
                'gold_bars' => 2000,
            ])
            ->getCharacter();

        $slot = $character->inventory->slots->filter(function ($slot) use ($item) {
            return $slot->item_id === $item->id;
        })->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/remove-gem/' . $character->id, [
                '_token' => csrf_token(),
                'slot_id' => $slot->id,
                'gem_id' => $gem->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Gem has been removed from the socket!', $jsonData['message']);
    }

    public function testRemoveAllGemsFromItem()
    {
        $item = $this->createItem([
            'socket_count' => 2,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $this->createGem()->id,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $this->createGem()->id,
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

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/remove-all-gems/' . $character->id . '/' . $slot->id, [
                '_token' => csrf_token(),
                'slot_id' => $slot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('All gems have been removed!', $jsonData['message']);
    }
}
