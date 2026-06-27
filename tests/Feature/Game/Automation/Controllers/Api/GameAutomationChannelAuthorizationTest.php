<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class GameAutomationChannelAuthorizationTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_user_can_authorize_their_own_delve_status_channel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('delve-status-updated-{userId}');
        $result = $callback($user, $user->id);

        $this->assertTrue($result);
    }

    public function test_user_cannot_authorize_another_users_delve_status_channel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('delve-status-updated-{userId}');
        $result = $callback($user, $user->id + 1);

        $this->assertFalse($result);
    }
}
