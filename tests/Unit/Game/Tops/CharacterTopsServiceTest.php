<?php

namespace Tests\Unit\Game\Tops;

use App\Game\Tops\Services\CharacterTopsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserLoginDuration;

class CharacterTopsServiceTest extends TestCase
{
    use CreateCharacter, CreateUser, CreateUserLoginDuration, RefreshDatabase;

    public function test_character_progression_sorts_by_reincarnation_before_level(): void
    {
        $user = $this->createUser();
        $first = $this->createCharacter(['user_id' => $user->id, 'name' => 'First', 'level' => 1, 'times_reincarnated' => 2]);
        $this->createCharacter(['user_id' => $user->id, 'name' => 'Second', 'level' => 999, 'times_reincarnated' => 1]);
        $this->createUserLoginDuration(['user_id' => $user->id, 'logged_in_at' => now(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        $data = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame($first->id, $data['rows'][0]['character_id']);
    }

    public function test_character_progression_leaderboard_includes_podium_rows_in_rank_order(): void
    {
        $user = $this->createUser();
        $this->createCharacter(['user_id' => $user->id, 'name' => 'First', 'level' => 30, 'times_reincarnated' => 3]);
        $this->createCharacter(['user_id' => $user->id, 'name' => 'Second', 'level' => 20, 'times_reincarnated' => 2]);
        $this->createCharacter(['user_id' => $user->id, 'name' => 'Third', 'level' => 10, 'times_reincarnated' => 1]);
        $this->createUserLoginDuration(['user_id' => $user->id, 'logged_in_at' => now(), 'last_activity' => now(), 'last_heart_beat' => now()]);

        $data = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'current_month']);

        $this->assertSame(1, $data['podium'][0]['rank']);
        $this->assertSame(2, $data['podium'][1]['rank']);
        $this->assertSame(3, $data['podium'][2]['rank']);
    }

    public function test_character_progression_current_month_and_all_time_use_cumulative_rows(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'name' => 'Cumulative', 'level' => 10, 'times_reincarnated' => 1]);
        $this->createUserLoginDuration(['user_id' => $user->id, 'logged_in_at' => now()->subMonths(2), 'last_activity' => now()->subMonths(2), 'last_heart_beat' => now()->subMonths(2)]);

        $currentMonth = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'current_month']);
        $allTime = $this->app->make(CharacterTopsService::class)->leaderboard(['period' => 'all_time']);

        $this->assertSame($character->id, $currentMonth['rows'][0]['character_id']);
        $this->assertSame($currentMonth['rows'][0]['character_id'], $allTime['rows'][0]['character_id']);
        $this->assertSame($currentMonth['rows'][0]['level'], $allTime['rows'][0]['level']);
    }
}
