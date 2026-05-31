<?php

namespace Tests\Unit\Game\Factions\FactionLoyalty\Events;

use App\Flare\Models\User;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyAutomationWarningState;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class FactionLoyaltyAutomationWarningStateTest extends TestCase
{
    public function testConstructorSetsWarningPayload(): void
    {
        $event = new FactionLoyaltyAutomationWarningState($this->user(), true, [
            'type' => 'bounty',
            'message' => 'Warning message.',
        ]);

        $this->assertTrue($event->has_warning);
        $this->assertEquals([
            'type' => 'bounty',
            'message' => 'Warning message.',
        ], $event->warning_notice);
    }

    public function testConstructorSetsClearedPayload(): void
    {
        $event = new FactionLoyaltyAutomationWarningState($this->user(), false, null);

        $this->assertFalse($event->has_warning);
        $this->assertNull($event->warning_notice);
    }

    public function testBroadcastOnReturnsPrivateFactionLoyaltyAutomationWarningChannel(): void
    {
        $event = new FactionLoyaltyAutomationWarningState($this->user(), false, null);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-faction-loyalty-automation-warning-123', $channel->name);
    }

    private function user(): User
    {
        $user = new User();
        $user->id = 123;

        return $user;
    }
}
