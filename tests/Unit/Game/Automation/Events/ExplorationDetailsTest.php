<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Flare\Models\User;
use App\Game\Automation\Events\ExplorationDetails;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class ExplorationDetailsTest extends TestCase
{
    public function testConstructorSetsUser(): void
    {
        $user = $this->user();

        $event = new ExplorationDetails($user, []);

        $this->assertEquals($user, $event->user);
    }

    public function testConstructorSetsDetails(): void
    {
        $details = [
            'current_character_health' => 100,
            'current_monster_health' => 50,
        ];

        $event = new ExplorationDetails($this->user(), $details);

        $this->assertEquals($details, $event->details);
    }

    public function testBroadcastOnReturnsPrivateAutomationAttackDetailsChannel(): void
    {
        $event = new ExplorationDetails($this->user(), []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-attack-details-123', $channel->name);
    }

    private function user(): User
    {
        $user = new User();
        $user->id = 123;

        return $user;
    }
}