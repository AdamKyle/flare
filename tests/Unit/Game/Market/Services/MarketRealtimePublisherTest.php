<?php

namespace Tests\Unit\Game\Market\Services;

use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Market\Services\MarketRealtimePublisher;
use App\Game\Market\Transformers\MarketItemsTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use League\Fractal\Manager;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMarketBoardListing;

class MarketRealtimePublisherTest extends TestCase
{
    use CreateItem, CreateMarketBoardListing, RefreshDatabase;

    private ?MarketRealtimePublisher $marketRealtimePublisher = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marketRealtimePublisher = new MarketRealtimePublisher(
            new Manager,
            new MarketItemsTransformer($this->app->make(ItemTransformer::class)),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->marketRealtimePublisher = null;
    }

    public function test_publish_broadcasts_only_unlocked_listings(): void
    {
        Event::fake([UpdateMarketBoardBroadcastEvent::class]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $unlockedListing = $this->createMarketBoardListing([
            'character_id' => $character->id,
            'item_id' => $this->createItem()->id,
            'listed_price' => 100,
            'is_locked' => false,
        ]);

        $this->createMarketBoardListing([
            'character_id' => $character->id,
            'item_id' => $this->createItem()->id,
            'listed_price' => 200,
            'is_locked' => true,
        ]);

        $this->marketRealtimePublisher->publish($character);

        Event::assertDispatched(UpdateMarketBoardBroadcastEvent::class, function (UpdateMarketBoardBroadcastEvent $event) use ($unlockedListing) {
            return collect($event->marketListings['data'])->pluck('id')->all() === [$unlockedListing->id];
        });
    }

    public function test_publish_uses_the_update_market_presence_channel(): void
    {
        Event::fake([UpdateMarketBoardBroadcastEvent::class]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->marketRealtimePublisher->publish($character);

        Event::assertDispatched(UpdateMarketBoardBroadcastEvent::class, function (UpdateMarketBoardBroadcastEvent $event) {
            return $event->broadcastOn()->name === 'presence-update-market';
        });
    }

    public function test_publish_broadcasts_listing_metadata_without_nested_item_details(): void
    {
        Event::fake([UpdateMarketBoardBroadcastEvent::class]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->createMarketBoardListing([
            'character_id' => $character->id,
            'item_id' => $this->createItem()->id,
            'listed_price' => 100,
            'is_locked' => false,
        ]);

        $this->marketRealtimePublisher->publish($character);

        Event::assertDispatched(UpdateMarketBoardBroadcastEvent::class, function (UpdateMarketBoardBroadcastEvent $event) {
            return ! array_key_exists('item', $event->marketListings['data'][0]);
        });
    }
}
