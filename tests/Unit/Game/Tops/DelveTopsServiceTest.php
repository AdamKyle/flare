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

    public function test_delve_tops_ranks_by_verified_strength_metric(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        DelveExploration::factory()->create(['character_id' => $character->id, 'increase_enemy_strength' => 3.5, 'started_at' => now()]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(3.5, $data['rows'][0]['strongest_enemy_increase']);
    }

    public function test_delve_tops_defaults_to_ranking_by_survived_duration(): void
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

    public function test_delve_tops_includes_total_floors_from_delve_log_count(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);
        $exploration = DelveExploration::factory()->create(['character_id' => $character->id, 'started_at' => now()]);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(2, $data['rows'][0]['total_floors']);
    }

    public function test_delve_tops_primary_metrics_use_requested_fields(): void
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

    public function test_delve_tops_detail_does_not_expose_unused_total_rounds_field(): void
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
