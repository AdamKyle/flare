<?php

namespace Tests\Unit\Game\Core\Jobs;

use App\Game\Core\Events\BuyItemEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Models\Item;
use App\Game\Core\Jobs\PurchaseItemsJob;
use App\Game\Messages\Events\ServerMessageEvent;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItem;

class PurchaseItemsJobsTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->createItem();

        Event::fake();
    }

    public function testInventoryIsFull() {
        $character = $this->character->updateCharacter([
            'inventory_max' => 0
        ])->getCharacter();

        PurchaseItemsJob::dispatch($character, Item::all());

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testNotEnoughGold() {
        $character = $this->character->updateCharacter([
            'gold' => 0
        ])->getCharacter();

        PurchaseItemsJob::dispatch($character, Item::all());

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testPurchaseAllItems() {
        $character = $this->character->updateCharacter([
            'gold' => 100
        ])->getCharacter();

        PurchaseItemsJob::dispatch($character, Item::all());

        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(BuyItemEvent::class);
    }
}
