<?php

namespace Tests\Unit\Game\Automation\Exploration\Events;

use App\Game\Automation\Exploration\Events\ExplorationAttackMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class ExplorationAttackMessageTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_broadcast_on_returns_private_automation_attack_messages_channel(): void
    {
        $event = new ExplorationAttackMessage($this->createAutomationEventUser(), []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-attack-messages-123', $channel->name);
    }
}
