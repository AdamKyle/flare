<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\ExplorationAttackMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class ExplorationAttackMessageTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_constructor_sets_user(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new ExplorationAttackMessage($user, []);

        $this->assertEquals($user, $event->user);
    }

    public function test_constructor_sets_messages(): void
    {
        $messages = [
            'Character attacked the monster.',
            'Monster took damage.',
        ];

        $event = new ExplorationAttackMessage($this->createAutomationEventUser(), $messages);

        $this->assertEquals($messages, $event->messages);
    }

    public function test_broadcast_on_returns_private_automation_attack_messages_channel(): void
    {
        $event = new ExplorationAttackMessage($this->createAutomationEventUser(), []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-attack-messages-123', $channel->name);
    }
}
