<?php

namespace Tests\Feature\Game\Tops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class TopsBroadcastChannelsTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_tops_character_leaderboard_channel_allows_authenticated_users(): void
    {
        $user = $this->createUser();
        $callback = Broadcast::driver()->getChannels()->get('tops-character-leaderboard');
        $result = $callback($user);

        $this->assertTrue($result);
    }
}
