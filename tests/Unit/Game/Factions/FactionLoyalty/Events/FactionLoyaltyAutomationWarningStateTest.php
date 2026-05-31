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
        $user = new User();
        $user->id = 123;
        $warningNotices = [
            [
                'id' => 10,
                'type' => 'bounty',
                'message' => 'Warning message.',
            ],
        ];

        $event = new FactionLoyaltyAutomationWarningState($user, true, $warningNotices);

        $this->assertTrue($event->has_warning);
        $this->assertEquals($warningNotices, $event->warning_notices);
        $this->assertEquals($warningNotices[0], $event->warning_notice);
    }

    public function testConstructorSetsClearedPayload(): void
    {
        $user = new User();
        $user->id = 123;

        $event = new FactionLoyaltyAutomationWarningState($user, false, []);

        $this->assertFalse($event->has_warning);
        $this->assertEquals([], $event->warning_notices);
        $this->assertNull($event->warning_notice);
    }

    public function testBroadcastOnReturnsPrivateFactionLoyaltyAutomationWarningChannel(): void
    {
        $user = new User();
        $user->id = 123;

        $event = new FactionLoyaltyAutomationWarningState($user, false, []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-faction-loyalty-automation-warning-123', $channel->name);
    }
}
