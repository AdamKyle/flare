<?php

namespace Tests\Unit\Flare\Events;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserSiteAccessStatistics;
use App\Flare\Events\SiteAccessedEvent;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;

class SiteAccessedEventTest extends TestCase {

    use CreateUser, CreateRole, CreateUserSiteAccessStatistics;


    public function setUp(): void {
        parent::setUp();

        $this->createAdmin($this->createAdminRole(), []);
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testSetsRecord() {
        event(new Login('auth', User::first(), false));

        $this->assertTrue(!is_null(UserSiteAccessStatistics::first()));
    }

    public function testSetsRecordWhenOneExists() {
        $this->createUserSiteAccessStatistics();

        event(new Login('auth', User::first(), false));

        $this->assertTrue(UserSiteAccessStatistics::count() > 1);
    }

    public function testSetsRecordWhenJustSigningIn() {
        $this->createUserSiteAccessStatistics();

        event(new Login('auth', User::first(), false));

        $this->assertTrue(UserSiteAccessStatistics::count() > 1);
    }

    public function testSetsRecordWithOutAdmin() {

        User::doesntHave('character')->first()->delete();

        event(new Login('auth', User::first(), false));

        $this->assertTrue(!is_null(UserSiteAccessStatistics::first()));
    }

    public function testSetsRecordWhenOneExistsWithOutAdmin() {
        User::doesntHave('character')->first()->delete();

        $this->createUserSiteAccessStatistics();

        event(new Login('auth', User::first(), false));

        $this->assertTrue(UserSiteAccessStatistics::count() > 1);
    }

    public function testSetsRecordWhenJustSigningInWithOutAdmin() {
        User::doesntHave('character')->first()->delete();

        $this->createUserSiteAccessStatistics();

        event(new Login('auth', User::first(), false));

        $this->assertTrue(UserSiteAccessStatistics::count() > 1);
    }
}
