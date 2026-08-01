<?php

namespace Tests\Unit\Game\Tops;

use App\Game\Tops\Services\ExplorationTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateUser;

class ExplorationTopsServiceTest extends TestCase
{
    use CreateCharacter, CreateExplorationLog, CreateUser, RefreshDatabase;

    public function test_exploration_tops_ranks_by_kills(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $this->createExplorationLog(['character_id' => $character->id, 'user_id' => $user->id, 'kills' => 10, 'started_at' => now()]);

        $data = $this->app->make(ExplorationTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(10, $data['rows'][0]['kills']);
    }

    public function test_exploration_tops_includes_length_of_time_seconds_from_started_and_ended_at(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(5),
            'ended_at' => now(),
        ]);

        $data = $this->app->make(ExplorationTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(300, $data['rows'][0]['length_of_time_seconds']);
    }

    public function test_exploration_current_month_and_all_time_use_cumulative_rows(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $this->createExplorationLog(['character_id' => $character->id, 'user_id' => $user->id, 'kills' => 10, 'started_at' => now()->subMonths(2), 'ended_at' => now()->subMonths(2)->addMinute()]);

        $currentMonth = $this->app->make(ExplorationTopsService::class)->leaderboard(['period' => 'current_month']);
        $allTime = $this->app->make(ExplorationTopsService::class)->leaderboard(['period' => 'all_time']);

        $this->assertSame(10, $currentMonth['rows'][0]['kills']);
        $this->assertSame($currentMonth['rows'][0]['kills'], $allTime['rows'][0]['kills']);
        $this->assertSame(['kills', 'length_of_time_seconds', 'xp_gained', 'skill_xp_gained'], collect($currentMonth['available_metrics'])->pluck('key')->all());
    }
}
