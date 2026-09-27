<?php

namespace Tests\Unit\Game\Automation\Delve\Events;

use App\Game\Automation\Delve\Events\DelveStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class DelveStatusUpdatedTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_broadcast_as_returns_delve_status_updated(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id, ['active' => false, 'completed' => false]);

        $this->assertSame('delve.status.updated', $event->broadcastAs());
    }

    public function test_broadcast_on_returns_private_user_scoped_channel(): void
    {
        $user = $this->createUser();

        $event = new DelveStatusUpdated($user->id, ['active' => false, 'completed' => false]);

        $this->assertSame('private-delve-status-updated-'.$user->id, $event->broadcastOn()->name);
    }

    public function test_broadcast_with_carries_the_full_status_snapshot(): void
    {
        $user = $this->createUser();

        $status = [
            'active' => true,
            'completed' => false,
            'pack_size' => 5,
            'totals' => ['rounds' => 2, 'wins' => 2, 'timeouts' => 0, 'pack_size' => 5, 'enemy_strength_increase' => 5.0],
        ];

        $event = new DelveStatusUpdated($user->id, $status);

        $this->assertSame([
            'user_id' => $user->id,
            'status' => $status,
        ], $event->broadcastWith());
    }
}
