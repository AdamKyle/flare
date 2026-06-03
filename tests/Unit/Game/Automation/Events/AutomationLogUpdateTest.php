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

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testConstructorSetsDefaultPayloadValues(): void
    {
        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Test automation message');

        $this->assertEquals('Test automation message', $event->message);
        $this->assertFalse($event->makeItalic);
        $this->assertFalse($event->isReward);
    }

    public function testConstructorSetsCustomPayloadValues(): void
    {
        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Reward automation message', true, true);

        $this->assertEquals('Reward automation message', $event->message);
        $this->assertTrue($event->makeItalic);
        $this->assertTrue($event->isReward);
    }

    public function testConstructorSetsTimeStamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 14:45:00', 'UTC'));

        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Test automation message');

        $this->assertEquals(now()->toJSON(), $event->timeStamp);
    }

    public function testBroadcastOnReturnsPrivateAutomationLogUpdateChannel(): void
    {
        $user = $this->createUser();

        $event = new AutomationLogUpdate($user->id, 'Test automation message');

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-log-update-'.$user->id, $channel->name);
    }
}