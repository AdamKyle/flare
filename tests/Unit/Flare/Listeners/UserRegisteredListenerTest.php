<?php

namespace Tests\Unit\Flare\Listeners;

use App\Admin\Events\AdminStatisticsDashboardUpdated;
use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Listeners\UserRegisteredListener;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Game\Core\Events\WhosPlayingStatisticsUpdated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUserSiteAccessStatistics;

class UserRegisteredListenerTest extends TestCase
{
    use CreateRole, CreateUser, CreateUserSiteAccessStatistics, RefreshDatabase;

    public function test_first_registration_record_broadcasts_the_site_statistics_chart_when_an_admin_exists(): void
    {
        $this->createAdminRole();
        $admin = $this->createUser();
        $admin->assignRole('Admin');
        $user = $this->createUser();

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class, UpdateSiteStatisticsChart::class]);

        (new UserRegisteredListener())->handle(new Registered($user));

        $this->assertSame(1, UserSiteAccessStatistics::count());
        Event::assertDispatched(UpdateSiteStatisticsChart::class);
    }

    public function test_first_registration_record_does_not_broadcast_the_chart_when_no_admin_exists(): void
    {
        $user = $this->createUser();

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class, UpdateSiteStatisticsChart::class]);

        (new UserRegisteredListener())->handle(new Registered($user));

        $this->assertSame(1, UserSiteAccessStatistics::count());
        Event::assertNotDispatched(UpdateSiteStatisticsChart::class);
    }

    public function test_new_day_creates_a_new_statistics_record(): void
    {
        $this->createUserSiteAccessStatistics(['amount_signed_in' => 3, 'amount_registered' => 1, 'invalid_ips' => [], 'invalid_user_ids' => [], 'created_at' => now()->subDays(2)]);
        $user = $this->createUser();

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class, UpdateSiteStatisticsChart::class]);

        (new UserRegisteredListener())->handle(new Registered($user));

        $this->assertSame(2, UserSiteAccessStatistics::count());
    }

    public function test_same_day_with_null_invalid_user_ids_increments_registered(): void
    {
        $existing = $this->createUserSiteAccessStatistics(['amount_signed_in' => 3, 'amount_registered' => 1, 'invalid_ips' => null, 'invalid_user_ids' => null]);
        $user = $this->createUser();

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class, UpdateSiteStatisticsChart::class]);

        (new UserRegisteredListener())->handle(new Registered($user));

        $latest = UserSiteAccessStatistics::where('amount_registered', $existing->amount_registered + 1)->first();
        $this->assertSame($existing->amount_registered + 1, $latest->amount_registered);
    }

    public function test_same_day_with_a_new_user_id_appends_and_increments(): void
    {
        $otherUser = $this->createUser();
        $existing = $this->createUserSiteAccessStatistics(['amount_signed_in' => 3, 'amount_registered' => 1, 'invalid_ips' => ['1.1.1.1'], 'invalid_user_ids' => [$otherUser->id]]);
        $user = $this->createUser();

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class, UpdateSiteStatisticsChart::class]);

        (new UserRegisteredListener())->handle(new Registered($user));

        $latest = UserSiteAccessStatistics::where('amount_registered', $existing->amount_registered + 1)->first();
        $this->assertSame($existing->amount_registered + 1, $latest->amount_registered);
        $this->assertContains($user->id, $latest->invalid_user_ids);
    }

    public function test_same_day_with_an_already_recorded_user_id_does_not_create_a_new_record(): void
    {
        $user = $this->createUser();
        $this->createUserSiteAccessStatistics(['amount_signed_in' => 3, 'amount_registered' => 1, 'invalid_ips' => ['1.1.1.1'], 'invalid_user_ids' => [$user->id]]);

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class, UpdateSiteStatisticsChart::class]);

        (new UserRegisteredListener())->handle(new Registered($user));

        $this->assertSame(1, UserSiteAccessStatistics::count());
    }

    public function test_same_day_with_a_new_user_id_broadcasts_the_chart_when_an_admin_exists(): void
    {
        $this->createAdminRole();
        $admin = $this->createUser();
        $admin->assignRole('Admin');

        $otherUser = $this->createUser();
        $this->createUserSiteAccessStatistics(['amount_signed_in' => 3, 'amount_registered' => 1, 'invalid_ips' => ['1.1.1.1'], 'invalid_user_ids' => [$otherUser->id]]);
        $user = $this->createUser();

        Event::fake([AdminStatisticsDashboardUpdated::class, WhosPlayingStatisticsUpdated::class, UpdateSiteStatisticsChart::class]);

        (new UserRegisteredListener())->handle(new Registered($user));

        Event::assertDispatched(UpdateSiteStatisticsChart::class);
    }
}
