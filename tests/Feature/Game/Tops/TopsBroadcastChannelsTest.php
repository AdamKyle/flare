<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class TopsBroadcastChannelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tops_character_leaderboard_channel_allows_authenticated_users(): void
    {
        $user = User::factory()->create();
        $callback = Broadcast::driver()->getChannels()->get('tops-character-leaderboard');
        $result = $callback($user);

        $this->assertTrue($result);
    }
}
