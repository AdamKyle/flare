<?php

namespace Tests\Unit\Game\Core\Events;

use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class BroadcastChannelAuthorizationTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function testUpdateMarketChannelDoesNotReturnFullUserModel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('update-market');
        $result = $callback($user);

        $this->assertNotInstanceOf(User::class, $result);
    }

    public function testUpdateMarketChannelReturnsOnlySafeMemberData(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('update-market');
        $result = $callback($user);

        $this->assertSame(['id' => $user->id], $result);
    }

    public function testChatChannelReturnsOnlySafeMemberData(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('chat');
        $result = $callback($user);

        $this->assertSame(['id' => $user->id], $result);
    }

    public function testAuthenticatedUserCanAuthorizeUpdateMarketChannel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('update-market');
        $result = $callback($user);

        $this->assertNotFalse($result);
        $this->assertNotNull($result);
    }
}
