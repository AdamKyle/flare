<?php

namespace Tests\Feature\Game\Automation\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class GameAutomationChannelAuthorizationTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function testUserCanAuthorizeTheirOwnDelveStatusChannel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('delve-status-updated-{userId}');
        $result = $callback($user, $user->id);

        $this->assertTrue($result);
    }

    public function testUserCannotAuthorizeAnotherUsersDelveStatusChannel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('delve-status-updated-{userId}');
        $result = $callback($user, $user->id + 1);

        $this->assertFalse($result);
    }
}
