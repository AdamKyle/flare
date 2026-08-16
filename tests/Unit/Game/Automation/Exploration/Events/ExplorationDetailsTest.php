<?php

namespace Tests\Unit\Game\Automation\Exploration\Events;

use App\Game\Automation\Exploration\Events\ExplorationDetails;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class ExplorationDetailsTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_broadcast_on_returns_private_automation_attack_details_channel(): void
    {
        $event = new ExplorationDetails($this->createAutomationEventUser(), []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-attack-details-123', $channel->name);
    }
}
