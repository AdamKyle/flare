<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\AutomationLogUpdate;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class AutomationLogUpdateTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_constructor_sets_time_stamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 14:45:00', 'UTC'));

        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Test automation message');

        $this->assertEquals(now()->toJSON(), $event->timeStamp);
    }

    public function test_broadcast_on_returns_private_automation_log_update_channel(): void
    {
        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Test automation message');

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-log-update-'.$user->id, $channel->name);
    }

    public function test_event_broadcast_payload_only_contains_expected_public_properties(): void
    {
        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Test automation message', true, true);

        $this->assertSame(
            ['message', 'makeItalic', 'isReward', 'timeStamp', 'socket'],
            array_keys(get_object_vars($event))
        );
    }
}
