<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Services\UserOnlineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserSession;

class UserOnlineServiceTest extends TestCase
{
    use CreateUser, CreateUserSession, RefreshDatabase;

    public function test_is_online_true_when_a_session_exists_for_the_user(): void
    {
        $user = $this->createUser();
        $this->createUserSession($user);

        $this->assertTrue((new UserOnlineService())->isOnline($user));
    }

    public function test_is_online_false_when_no_session_exists_for_the_user(): void
    {
        $user = $this->createUser();

        $this->assertFalse((new UserOnlineService())->isOnline($user));
    }

    public function test_get_users_online_returns_users_with_a_recent_session(): void
    {
        $user = $this->createUser();
        $this->createUserSession($user);

        $usersOnline = (new UserOnlineService())->getUsersOnline();

        $this->assertTrue($usersOnline->contains('id', $user->id));
    }

    public function test_get_users_online_query_returns_a_query_for_recent_sessions(): void
    {
        $user = $this->createUser();
        $this->createUserSession($user);

        $query = (new UserOnlineService())->getUsersOnlineQuery();

        $this->assertTrue($query->get()->contains('user_id', $user->id));
    }
}
