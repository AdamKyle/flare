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

    public function testExplorationTopsIncludesLengthOfTimeSecondsFromStartedAndEndedAt(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        ExplorationLog::factory()->create([
            'character_id' => $character->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(5),
            'ended_at' => now(),
        ]);

        $data = $this->app->make(ExplorationTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(300, $data['rows'][0]['length_of_time_seconds']);
    }

    public function testExplorationCurrentMonthAndAllTimeUseCumulativeRows(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        ExplorationLog::factory()->create(['character_id' => $character->id, 'user_id' => $user->id, 'kills' => 10, 'started_at' => now()->subMonths(2), 'ended_at' => now()->subMonths(2)->addMinute()]);

        $currentMonth = $this->app->make(ExplorationTopsService::class)->leaderboard(['period' => 'current_month']);
        $allTime = $this->app->make(ExplorationTopsService::class)->leaderboard(['period' => 'all_time']);

        $this->assertSame(10, $currentMonth['rows'][0]['kills']);
        $this->assertSame($currentMonth['rows'][0]['kills'], $allTime['rows'][0]['kills']);
        $this->assertSame(['kills', 'length_of_time_seconds', 'xp_gained', 'skill_xp_gained'], collect($currentMonth['available_metrics'])->pluck('key')->all());
    }
}
