<?php

namespace Tests\Feature\Game\NpcActions\LabyrinthOracle\Controllers\Api;

use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\WeaponTypes;
use App\Game\Gambler\Values\CurrencyValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class LabyrinthOracleControllerTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateGem, CreateItemAffix;

    private ?CharacterFactory $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testInventoryItems() {
        $basicItem = $this->createItem([
            'name' => 'basic item'
        ]);
        $artifactItem = $this->createItem([
            'name' => 'artifact item',
            'type' => 'artifact',
        ]);
        $trinketItem = $this->createItem([
            'name' => 'trinket item',
            'type' => 'trinket',
        ]);
        $enchantedItem = $this->createItem([
            'name' => 'enchanted item',
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
            ])->id,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($basicItem)
            ->giveItem($enchantedItem)
            ->giveItem($trinketItem)
            ->giveItem($artifactItem)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'. $character->id .'/labyrinth-oracle');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(2, $jsonData['inventory']);
    }

    public function testInventoryItemsWithOneOfEach() {
        $basicItem = $this->createItem([
            'name' => 'basic item'
        ]);
        $artifactItem = $this->createItem([
            'name' => 'artifact item',
            'type' => 'artifact',
        ]);
        $trinketItem = $this->createItem([
            'name' => 'trinket item',
            'type' => 'trinket',
        ]);
        $enchantedItem = $this->createItem([
            'name' => 'enchanted item',
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
            ])->id,
        ]);

        $gemItem = $this->createItem([
            'name' => 'gem item',
            'socket_count' => 1,
        ]);

        $gemItem->sockets()->create([
            'gem_id' => $this->createGem()->id,
            'item_id' => $gemItem->id,
        ]);

        $gemItem = $gemItem->refresh();

        $holyItem = $this->createItem([
            'name' => 'holy item',
        ]);

        $holyItem->appliedHolyStacks()->create([
            'item_id' => $holyItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $holyItem = $holyItem->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($basicItem)
            ->giveItem($enchantedItem)
            ->giveItem($trinketItem)
            ->giveItem($artifactItem)
            ->giveItem($gemItem)
            ->giveItem($holyItem)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'. $character->id .'/labyrinth-oracle');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(4, $jsonData['inventory']);
    }

    public function testUseLabyrinthOracleToTransferItem() {
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
        $itemToTransferTo   = $this->createItem();

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

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'. $character->id .'/transfer-attributes', [
                'item_id_from' => $itemToTransferFrom->id,
                'item_id_to' => $itemToTransferTo->id,
            ]);


        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(2, count($jsonData['inventory']));

        Event::assertDispatched(ServerMessageEvent::class);
    }
}
