<?php

namespace Tests\Unit\Game\GuideQuests\Events;

use App\Game\GuideQuests\Events\ShowGuideQuestCompletedToast;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class ShowGuideQuestCompletedToastTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_event_broadcasts_completion_state_on_the_users_private_channel(): void
    {
        $user = $this->createUser();
        $completedEvent = new ShowGuideQuestCompletedToast($user, true);

        $this->assertTrue($completedEvent->showQuestCompleted);

        $channel = $completedEvent->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-guide-quest-completed-toast-'.$user->id, $channel->name);

        $clearedEvent = new ShowGuideQuestCompletedToast($user, false);

        $this->assertFalse($clearedEvent->showQuestCompleted);
    }
}
