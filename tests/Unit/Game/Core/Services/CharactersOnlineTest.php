<?php

namespace Tests\Unit\Game\Core\Services;

use App\Game\Core\Services\CharactersOnline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserLoginDuration;

class CharactersOnlineTest extends TestCase
{
    use CreateUser, CreateUserLoginDuration, RefreshDatabase;

    public function test_current_online_data_includes_character_name_level_map_and_duration(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['name' => 'Online Details Character', 'level' => 77]);
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subMinutes(10),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => null,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(0)->getCharacterOnlineData();

        $this->assertSame('Online Details Character', $result['characters_online'][0]['name']);
        $this->assertSame(77, $result['characters_online'][0]['level']);
        $this->assertSame('Surface', $result['characters_online'][0]['map']);
        $this->assertGreaterThanOrEqual(600, $result['characters_online'][0]['duration']);
    }

    public function test_current_online_data_includes_currently_exploring_and_activity_timestamps(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subMinutes(5),
            'last_activity' => now()->subMinutes(2),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => null,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(0)->getCharacterOnlineData();

        $this->assertIsBool($result['characters_online'][0]['currently_exploring']);
        $this->assertNotNull($result['characters_online'][0]['last_activity']);
        $this->assertNotNull($result['characters_online'][0]['last_heart_beat']);
    }

    public function test_current_online_data_uses_open_login_duration_rows(): void
    {
        $onlineCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $offlineCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $onlineCharacter->user_id,
            'logged_in_at' => now()->subMinutes(5),
            'last_activity' => now(),
            'last_heart_beat' => now(),
            'duration_in_seconds' => null,
        ]);
        $this->createUserLoginDuration([
            'user_id' => $offlineCharacter->user_id,
            'logged_in_at' => now()->subMinutes(30),
            'logged_out_at' => now()->subMinutes(10),
            'last_activity' => now()->subMinutes(10),
            'last_heart_beat' => now()->subMinutes(10),
            'duration_in_seconds' => 1200,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(0)->getCharacterOnlineData();

        $this->assertCount(1, $result['characters_online']);
        $this->assertSame($onlineCharacter->name, $result['characters_online'][0]['name']);
    }

    public function test_current_online_excludes_stale_open_login_rows_with_old_heartbeat(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subHours(2),
            'last_activity' => now()->subHour(),
            'last_heart_beat' => now()->subMinutes(31),
            'duration_in_seconds' => null,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(0)->getCharacterOnlineData();

        $this->assertCount(0, $result['characters_online']);
    }

    public function test_current_online_includes_open_login_rows_with_recent_heartbeat(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subMinutes(10),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => null,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(0)->getCharacterOnlineData();

        $this->assertCount(1, $result['characters_online']);
        $this->assertSame($character->name, $result['characters_online'][0]['name']);
    }

    public function test_current_online_excludes_rows_with_completed_duration(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subMinutes(15),
            'logged_out_at' => now()->subMinute(),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => 900,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(0)->getCharacterOnlineData();

        $this->assertCount(0, $result['characters_online']);
    }

    public function test_current_online_excludes_rows_with_logged_out_at_set(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subMinutes(15),
            'logged_out_at' => now()->subMinute(),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => null,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(0)->getCharacterOnlineData();

        $this->assertCount(0, $result['characters_online']);
    }

    public function test_historical_filter_returns_total_duration_and_never_marks_exploring(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subDays(2),
            'logged_out_at' => now()->subDays(2)->addMinutes(30),
            'last_activity' => now()->subDays(2)->addMinutes(30),
            'last_heart_beat' => now()->subDays(2)->addMinutes(30),
            'duration_in_seconds' => 1800,
        ]);
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subDay(),
            'logged_out_at' => now()->subDay()->addMinutes(15),
            'last_activity' => now()->subDay()->addMinutes(15),
            'last_heart_beat' => now()->subDay()->addMinutes(15),
            'duration_in_seconds' => 900,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(7)->getCharacterOnlineData();

        $this->assertSame(2700, $result['characters_online'][0]['duration']);
        $this->assertFalse($result['characters_online'][0]['currently_exploring']);
    }

    public function test_historical_filter_returns_completed_duration_rows_only(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subDays(2),
            'logged_out_at' => now()->subDays(2)->addMinutes(10),
            'last_activity' => now()->subDays(2)->addMinutes(10),
            'last_heart_beat' => now()->subDays(2)->addMinutes(10),
            'duration_in_seconds' => 600,
        ]);
        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now()->subDay(),
            'last_activity' => now()->subMinute(),
            'last_heart_beat' => now()->subMinute(),
            'duration_in_seconds' => null,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(7)->getCharacterOnlineData();

        $this->assertSame(600, $result['characters_online'][0]['duration']);
    }

    public function test_users_without_characters_are_skipped(): void
    {
        $user = $this->createUser();
        $this->createUserLoginDuration([
            'user_id' => $user->id,
            'logged_in_at' => now()->subMinutes(5),
            'last_activity' => now(),
            'last_heart_beat' => now(),
            'duration_in_seconds' => null,
        ]);

        $result = resolve(CharactersOnline::class)->setFilterType(0)->getCharacterOnlineData();

        $this->assertCount(0, $result['characters_online']);
    }
}
