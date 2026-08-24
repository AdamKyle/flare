<?php

namespace Tests\Unit\Flare\Listeners;

use App\Admin\Events\AdminStatisticsDashboardUpdated;
use App\Flare\Listeners\RecordUserLogOutTimeListener;
use App\Game\Core\Events\WhosPlayingStatisticsUpdated;
use Carbon\Carbon;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserLoginDuration;

class RecordUserLogOutTimeListenerTest extends TestCase
{
    use CreateRole, CreateUser, CreateUserLoginDuration, RefreshDatabase;

    public function test_does_nothing_when_no_user_is_present(): void
    {
        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', null));

        Event::assertNotDispatched(AdminStatisticsDashboardUpdated::class);
    }

    public function test_does_nothing_for_an_admin_user(): void
    {
        $this->createAdminRole();
        $user = $this->createUser();
        $user->assignRole('Admin');

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        Event::assertNotDispatched(AdminStatisticsDashboardUpdated::class);
    }

    public function test_does_nothing_when_no_open_session_is_found(): void
    {
        $user = $this->createUser();

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        Event::assertNotDispatched(AdminStatisticsDashboardUpdated::class);
    }

    public function test_closes_the_open_session_and_broadcasts_statistics_updates(): void
    {
        $user = $this->createUser();
        $session = $this->createUserLoginDuration([
            'user_id' => $user->id,
            'logged_in_at' => now()->subHour(),
            'last_activity' => now()->subMinutes(30),
            'last_heart_beat' => now()->subMinutes(30),
            'logged_out_at' => null,
            'duration_in_seconds' => null,
        ]);

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $session->refresh();
        $this->assertNotNull($session->logged_out_at);
        $this->assertNotNull($session->duration_in_seconds);
        Event::assertDispatched(AdminStatisticsDashboardUpdated::class);
        Event::assertDispatched(WhosPlayingStatisticsUpdated::class);
    }

    public function test_clamps_the_logout_time_to_the_login_time_when_it_would_otherwise_precede_it(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');

        $user = $this->createUser();
        $session = $this->createUserLoginDuration([
            'user_id' => $user->id,
            'logged_in_at' => now()->addMinute(),
            'last_activity' => now(),
            'last_heart_beat' => now(),
            'logged_out_at' => null,
            'duration_in_seconds' => null,
        ]);

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class]);

        (new RecordUserLogOutTimeListener())->handle(new Logout('web', $user));

        $session->refresh();
        $this->assertTrue($session->logged_out_at->equalTo($session->logged_in_at));
        $this->assertSame(0, $session->duration_in_seconds);
    }
}
