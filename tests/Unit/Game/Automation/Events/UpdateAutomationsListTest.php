<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\UpdateAutomationsList;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class UpdateAutomationsListTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_broadcast_on_returns_private_automations_list_channel(): void
    {
        $event = new UpdateAutomationsList($this->createAutomationEventUser(), new Collection());

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automations-list-123', $channel->name);
    }
}
