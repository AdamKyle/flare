<?php

namespace Tests\Unit\Game\Tops;

use App\Game\Tops\Services\DelveTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateDelveLog;
use Tests\Traits\CreateUser;

class DelveTopsServiceTest extends TestCase
{
    use CreateCharacter, CreateDelveExploration, CreateDelveLog, CreateUser, RefreshDatabase;

    public function test_delve_tops_ranks_by_verified_strength_metric(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $this->createDelveExploration(['character_id' => $character->id, 'increase_enemy_strength' => 3.5, 'started_at' => now()]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(3.5, $data['rows'][0]['strongest_enemy_increase']);
    }

    public function test_delve_tops_defaults_to_ranking_by_survived_duration(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $this->createDelveExploration([
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
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $exploration = $this->createDelveExploration(['character_id' => $character->id, 'started_at' => now()]);
        $this->createDelveLog(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);
        $this->createDelveLog(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(2, $data['rows'][0]['total_floors']);
    }

    public function test_delve_tops_primary_metrics_use_requested_fields(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $exploration = $this->createDelveExploration([
            'character_id' => $character->id,
            'increase_enemy_strength' => 27.56,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
        $this->createDelveLog(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);

        $data = $this->app->make(DelveTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(['strongest_enemy_increase', 'survived_duration_seconds', 'total_floors'], collect($data['available_metrics'])->pluck('key')->all());
        $this->assertSame(27.56, $data['rows'][0]['strongest_enemy_increase']);
        $this->assertSame(600, $data['rows'][0]['survived_duration_seconds']);
        $this->assertArrayNotHasKey('total_rounds', $data['rows'][0]);
    }

    public function test_delve_tops_detail_does_not_expose_unused_total_rounds_field(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $exploration = $this->createDelveExploration(['character_id' => $character->id, 'started_at' => now()]);
        $this->createDelveLog(['character_id' => $character->id, 'delve_exploration_id' => $exploration->id]);

        $data = $this->app->make(DelveTopsService::class)->detail($exploration);

        $this->assertArrayNotHasKey('total_rounds', $data);
        $this->assertSame(1, $data['total_floors']);
    }
}
