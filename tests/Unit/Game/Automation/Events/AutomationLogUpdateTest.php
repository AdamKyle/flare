<?php

namespace Tests\Unit\Game\Automation\Events;

use App\Game\Automation\Events\AutomationLogUpdate;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class AutomationLogUpdateTest extends TestCase
{
    public function testConstructorSetsDefaultPayloadValues(): void
    {
        $event = new AutomationLogUpdate(1, 'Test automation message');

        $this->assertEquals('Test automation message', $event->message);
        $this->assertFalse($event->makeItalic);
        $this->assertFalse($event->isReward);
    }

    public function testConstructorSetsCustomPayloadValues(): void
    {
        $event = new AutomationLogUpdate(1, 'Reward automation message', true, true);

        $this->assertEquals('Reward automation message', $event->message);
        $this->assertTrue($event->makeItalic);
        $this->assertTrue($event->isReward);
    }

    public function testBroadcastOnReturnsPrivateAutomationLogUpdateChannel(): void
    {
        $event = new AutomationLogUpdate(123, 'Test automation message');

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertEquals('private-automation-log-update-123', $channel->name);
    }
}