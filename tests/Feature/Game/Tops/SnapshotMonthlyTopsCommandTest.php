<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\TopsMonthlySnapshot;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SnapshotMonthlyTopsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_snapshot_command_accepts_explicit_period_start_and_end(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id, 'level' => 10]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => '2026-05-02 00:00:00', 'last_activity' => '2026-05-02 00:00:00', 'last_heart_beat' => '2026-05-02 00:00:00']);

        $exitCode = $this->artisan('game:tops:snapshot-monthly', ['--period-start' => '2026-05-01', '--period-end' => '2026-05-31']);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(TopsMonthlySnapshot::where('board_type', 'characters')->whereDate('period_start', '2026-05-01')->exists());
    }
}
