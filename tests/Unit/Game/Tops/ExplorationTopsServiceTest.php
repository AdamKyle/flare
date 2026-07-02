<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\User;
use App\Game\Tops\Services\ExplorationTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExplorationTopsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testExplorationTopsRanksByKills(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        ExplorationLog::factory()->create(['character_id' => $character->id, 'user_id' => $user->id, 'kills' => 10, 'started_at' => now()]);

        $data = $this->app->make(ExplorationTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(10, $data['rows'][0]['kills']);
    }
}
