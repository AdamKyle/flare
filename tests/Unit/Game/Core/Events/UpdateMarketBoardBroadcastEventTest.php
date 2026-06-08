<?php

namespace Tests\Unit\Game\Core\Events;

use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UpdateMarketBoardBroadcastEventTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_market_listings_and_character_gold_are_in_broadcast_payload(): void
    {
        $user = $this->createUser();
        $event = new UpdateMarketBoardBroadcastEvent($user, [['item' => 'sword']], 500);

        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('marketListings', $payload);
        $this->assertArrayHasKey('characterGold', $payload);
    }

    public function test_user_is_not_in_broadcast_payload(): void
    {
        $user = $this->createUser();
        $event = new UpdateMarketBoardBroadcastEvent($user, [], 0);

        $payload = $event->broadcastWith();

        $this->assertArrayNotHasKey('user', $payload);
    }

    public function test_market_listings_is_public_property(): void
    {
        $user = $this->createUser();
        $listings = [['item' => 'sword']];
        $event = new UpdateMarketBoardBroadcastEvent($user, $listings, 100);

        $this->assertSame($listings, $event->marketListings);
    }

    public function test_character_gold_is_public_property(): void
    {
        $user = $this->createUser();
        $event = new UpdateMarketBoardBroadcastEvent($user, [], 250);

        $this->assertSame(250, $event->characterGold);
    }
}
