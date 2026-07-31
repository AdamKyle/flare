<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\User;
use App\Game\Tops\Services\DelveTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelveTopsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testDelveTopsRanksByVerifiedStrengthMetric(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        DelveExploration::factory()->create(['character_id' => $character->id, 'increase_enemy_strength' => 3.5, 'started_at' => now()]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(3.5, $data['rows'][0]['strongest_enemy_increase']);
    }

    public function testDelveTopsDefaultsToRankingBySurvivedDuration(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        DelveExploration::factory()->create([
            'character_id' => $character->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame('survived_duration_seconds', $data['metric']);
        $this->assertSame(600, $data['rows'][0]['survived_duration_seconds']);
    }

    public function testDelveTopsIncludesTotalFloorsFromDelveLogCount(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $exploration = DelveExploration::factory()->create(['character_id' => $character->id, 'started_at' => now()]);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(2, $data['rows'][0]['total_floors']);
    }

    public function testDelveTopsPrimaryMetricsUseRequestedFields(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $exploration = DelveExploration::factory()->create([
            'character_id' => $character->id,
            'increase_enemy_strength' => 27.56,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(['strongest_enemy_increase', 'survived_duration_seconds', 'total_floors'], collect($data['available_metrics'])->pluck('key')->all());
        $this->assertSame(27.56, $data['rows'][0]['strongest_enemy_increase']);
        $this->assertSame(600, $data['rows'][0]['survived_duration_seconds']);
        $this->assertArrayNotHasKey('total_rounds', $data['rows'][0]);
    }

    public function testDelveTopsDetailDoesNotExposeUnusedTotalRoundsField(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $exploration = DelveExploration::factory()->create(['character_id' => $character->id, 'started_at' => now()]);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);

        $data = $this->app->make(DelveTopsService::class)->detail($exploration);

        $this->assertArrayNotHasKey('total_rounds', $data);
        $this->assertSame(1, $data['total_floors']);
    }
}
