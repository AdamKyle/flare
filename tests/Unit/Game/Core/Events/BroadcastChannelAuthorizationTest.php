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

    public function test_update_market_channel_does_not_return_full_user_model(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('update-market');
        $result = $callback($user);

        $this->assertNotInstanceOf(User::class, $result);
    }

    public function test_update_market_channel_returns_only_safe_member_data(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('update-market');
        $result = $callback($user);

        $this->assertSame(['id' => $user->id], $result);
    }

    public function test_chat_channel_returns_only_safe_member_data(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('chat');
        $result = $callback($user);

        $this->assertSame(['id' => $user->id], $result);
    }

    public function test_authenticated_user_can_authorize_update_market_channel(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('update-market');
        $result = $callback($user);

        $this->assertNotFalse($result);
        $this->assertNotNull($result);
    }
}
