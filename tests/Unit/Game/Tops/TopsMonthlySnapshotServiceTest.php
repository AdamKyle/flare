<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\TopsMonthlySnapshot;
use App\Game\Tops\Services\TopsMonthlySnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserLoginDuration;

class TopsMonthlySnapshotServiceTest extends TestCase
{
    use CreateCharacter, CreateUser, CreateUserLoginDuration, RefreshDatabase;

    public function test_monthly_snapshot_service_creates_character_progression_rows(): void
    {
        $user = $this->createUser();
        $this->createCharacter(['user_id' => $user->id, 'level' => 10]);
        $this->createUserLoginDuration(['user_id' => $user->id, 'logged_in_at' => '2026-05-02 00:00:00', 'last_activity' => '2026-05-02 00:00:00', 'last_heart_beat' => '2026-05-02 00:00:00']);

        $count = $this->app->make(TopsMonthlySnapshotService::class)->snapshot(now()->parse('2026-05-01'), now()->parse('2026-05-31'));

        $this->assertGreaterThan(0, $count);
        $this->assertTrue(TopsMonthlySnapshot::where('board_type', 'characters')->where('metric_key', 'progression')->exists());
    }
}
