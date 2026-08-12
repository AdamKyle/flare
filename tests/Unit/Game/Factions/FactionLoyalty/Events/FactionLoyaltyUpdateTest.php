<?php

namespace Tests\Unit\Game\Factions\FactionLoyalty\Events;

use App\Flare\Models\User;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyUpdate;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class FactionLoyaltyUpdateTest extends TestCase
{
    public function test_broadcast_on_returns_private_faction_loyalty_update_channel(): void
    {
        $user = new User();
        $user->id = 123;
        $factionLoyalty = ['id' => 5, 'current_level' => 2];

        $event = new FactionLoyaltyUpdate($user, $factionLoyalty);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-faction-loyalty-update-123', $channel->name);
        $this->assertSame($factionLoyalty, $event->factionLoyalty);
    }
}
