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

    public function test_constructor_sets_default_payload_values(): void
    {
        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Test automation message');

        $this->assertEquals('Test automation message', $event->message);
        $this->assertFalse($event->makeItalic);
        $this->assertFalse($event->isReward);
    }

    public function test_constructor_sets_custom_payload_values(): void
    {
        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Reward automation message', true, true);

        $this->assertEquals('Reward automation message', $event->message);
        $this->assertTrue($event->makeItalic);
        $this->assertTrue($event->isReward);
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
}
