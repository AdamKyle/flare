<?php

namespace Tests\Unit\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use App\Game\Tops\Services\CharacterTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CharacterTopsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testCharacterProgressionSortsByReincarnationBeforeLevel(): void
    {
        $user = User::factory()->create();
        $first = Character::factory()->create(['user_id' => $user->id, 'name' => 'First', 'level' => 1, 'times_reincarnated' => 2]);
        Character::factory()->create(['user_id' => $user->id, 'name' => 'Second', 'level' => 999, 'times_reincarnated' => 1]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        $data = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame($first->id, $data['rows'][0]['character_id']);
    }

    public function testCharacterProgressionLeaderboardIncludesPodiumRowsInRankOrder(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id, 'name' => 'First', 'level' => 30, 'times_reincarnated' => 3]);
        Character::factory()->create(['user_id' => $user->id, 'name' => 'Second', 'level' => 20, 'times_reincarnated' => 2]);
        Character::factory()->create(['user_id' => $user->id, 'name' => 'Third', 'level' => 10, 'times_reincarnated' => 1]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        $data = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(1, $data['podium'][0]['rank']);
        $this->assertSame(2, $data['podium'][1]['rank']);
        $this->assertSame(3, $data['podium'][2]['rank']);
    }

    public function testCharacterProgressionCurrentMonthAndAllTimeUseCumulativeRows(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id, 'name' => 'Cumulative', 'level' => 10, 'times_reincarnated' => 1]);
        UserLoginDuration::factory()->create(['user_id' => $user->id, 'logged_in_at' => now()->subMonths(2), 'last_activity' => now()->subMonths(2), 'last_heart_beat' => now()->subMonths(2)]);

        $currentMonth = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'current_month']);
        $allTime = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'all_time']);

        $this->assertSame($character->id, $currentMonth['rows'][0]['character_id']);
        $this->assertSame($currentMonth['rows'][0]['character_id'], $allTime['rows'][0]['character_id']);
        $this->assertSame($currentMonth['rows'][0]['level'], $allTime['rows'][0]['level']);
    }

    public function testCharacterLeaderboardRecordsCurrentPerCharacterActivityQueryPattern(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        Character::factory()->create(['user_id' => $firstUser->id, 'name' => 'First Query Character']);
        Character::factory()->create(['user_id' => $secondUser->id, 'name' => 'Second Query Character']);
        UserLoginDuration::factory()->create([
            'user_id' => $firstUser->id,
            'logged_in_at' => now()->subMinute(),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
        ]);
        UserLoginDuration::factory()->create([
            'user_id' => $secondUser->id,
            'logged_in_at' => now(),
            'last_activity' => now(),
            'last_heart_beat' => now(),
        ]);
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $data = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'current_month']);
        $activityQueries = collect($queries)->filter(
            fn (string $sql): bool => str_contains($sql, 'user_login_durations')
                || str_contains($sql, 'sessions')
        );

        $this->assertCount(2, $data['rows']);
        $this->assertCount(2, $activityQueries);
    }
}
