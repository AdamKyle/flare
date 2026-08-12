<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\ExplorationOutputUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class ExplorationOutputUpdatedTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_broadcast_on_returns_private_exploration_output_channel(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new ExplorationOutputUpdated($user, 'attack', ['message' => 'You hit the monster.']);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-exploration-output-123', $channel->name);
        $this->assertSame('attack', $event->type);
        $this->assertSame(['message' => 'You hit the monster.'], $event->output);
    }
}
