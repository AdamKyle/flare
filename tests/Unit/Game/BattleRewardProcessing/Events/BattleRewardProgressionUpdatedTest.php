<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Events;

use App\Game\BattleRewardProcessing\Events\BattleRewardProgressionUpdated;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class BattleRewardProgressionUpdatedTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_broadcasts_on_the_users_private_battle_reward_progression_channel(): void
    {
        $user = $this->createUser();

        $event = new BattleRewardProgressionUpdated($user->id, 12, 5, 40, 100);

        $this->assertSame('private-battle-reward-progression-'.$user->id, $event->broadcastOn()->name);
    }

    public function test_public_payload_carries_the_progression_snapshot_without_the_user_id(): void
    {
        $user = $this->createUser();

        $event = new BattleRewardProgressionUpdated($user->id, 12, 5, 40, 100);

        $this->assertSame(
            [
                'requestId' => 12,
                'level' => 5,
                'xp' => 40,
                'xpNext' => 100,
                'complete' => false,
                'socket' => null,
            ],
            get_object_vars($event),
        );
    }

    public function test_is_broadcast_immediately_instead_of_waiting_on_the_broadcast_queue(): void
    {
        Queue::fake();

        $user = $this->createUser();

        $event = new BattleRewardProgressionUpdated($user->id, 0, null, null, null, true);

        event($event);

        $this->assertInstanceOf(ShouldBroadcastNow::class, $event);
        Queue::assertNotPushed(BroadcastEvent::class);
    }
}
