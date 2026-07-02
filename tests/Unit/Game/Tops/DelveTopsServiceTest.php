<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
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
}
