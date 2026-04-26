<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Flare\Models\User;
use App\Game\Automation\Events\ExplorationAttackMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class ExplorationAttackMessageTest extends TestCase
{
    public function testConstructorSetsUser(): void
    {
        $user = $this->user();

        $event = new ExplorationAttackMessage($user, []);

        $this->assertEquals($user, $event->user);
    }

    public function testConstructorSetsMessages(): void
    {
        $messages = [
            'Character attacked the monster.',
            'Monster took damage.',
        ];

        $event = new ExplorationAttackMessage($this->user(), $messages);

        $this->assertEquals($messages, $event->messages);
    }

    public function testBroadcastOnReturnsPrivateAutomationAttackMessagesChannel(): void
    {
        $event = new ExplorationAttackMessage($this->user(), []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-attack-messages-123', $channel->name);
    }

    private function user(): User
    {
        $user = new User();
        $user->id = 123;

        return $user;
    }
}