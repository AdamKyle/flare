<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Flare\Models\User;
use App\Game\Automation\Events\UpdateAutomationsList;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class UpdateAutomationsListTest extends TestCase
{
    public function test_constructor_sets_user(): void
    {
        $user = $this->user();

        $event = new UpdateAutomationsList($user, new Collection());

        $this->assertEquals($user, $event->user);
    }

    public function test_constructor_sets_automations(): void
    {
        $automations = new Collection();

        $event = new UpdateAutomationsList($this->user(), $automations);

        $this->assertEquals($automations, $event->automations);
    }

    public function test_broadcast_on_returns_private_automations_list_channel(): void
    {
        $event = new UpdateAutomationsList($this->user(), new Collection());

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automations-list-123', $channel->name);
    }

    private function user(): User
    {
        $user = new User();
        $user->id = 123;

        return $user;
    }
}
