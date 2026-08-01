<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\ExplorationDetails;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;
use Tests\Traits\CreateAutomationEventUser;

class ExplorationDetailsTest extends TestCase
{
    use CreateAutomationEventUser;

    public function test_constructor_sets_user(): void
    {
        $user = $this->createAutomationEventUser();

        $event = new ExplorationDetails($user, []);

        $this->assertEquals($user, $event->user);
    }

    public function test_constructor_sets_details(): void
    {
        $details = [
            'current_character_health' => 100,
            'current_monster_health' => 50,
        ];

        $event = new ExplorationDetails($this->createAutomationEventUser(), $details);

        $this->assertEquals($details, $event->details);
    }

    public function test_broadcast_on_returns_private_automation_attack_details_channel(): void
    {
        $event = new ExplorationDetails($this->createAutomationEventUser(), []);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-attack-details-123', $channel->name);
    }
}
