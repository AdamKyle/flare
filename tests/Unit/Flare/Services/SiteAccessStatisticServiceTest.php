<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Services\SiteAccessStatisticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUserSiteAccessStatistics;

class SiteAccessStatisticServiceTest extends TestCase
{
    use CreateUserSiteAccessStatistics, RefreshDatabase;

    public function test_get_registered_returns_24_hourly_labels_and_data_points_for_today(): void
    {
        $this->createUserSiteAccessStatistics(['amount_registered' => 3, 'amount_signed_in' => 5, 'created_at' => now()]);

        $result = (new SiteAccessStatisticService())->setAttribute('amount_registered')->setDaysPast(0)->getRegistered();

        $this->assertCount(24, $result['labels']);
        $this->assertCount(24, $result['data']);
        $this->assertContains(3, $result['data']);
    }

    public function test_get_signed_in_returns_daily_labels_and_data_points_for_a_week(): void
    {
        $this->createUserSiteAccessStatistics(['amount_registered' => 3, 'amount_signed_in' => 5, 'created_at' => now()->subDays(2)]);

        $result = (new SiteAccessStatisticService())->setAttribute('amount_signed_in')->setDaysPast(7)->getSignedIn();

        $this->assertCount(8, $result['labels']);
        $this->assertCount(8, $result['data']);
        $this->assertContains(5, $result['data']);
    }

    public function test_get_signed_in_supports_the_fourteen_day_time_frame(): void
    {
        $this->createUserSiteAccessStatistics(['amount_registered' => 1, 'amount_signed_in' => 2, 'created_at' => now()->subDays(1)]);

        $result = (new SiteAccessStatisticService())->setAttribute('amount_signed_in')->setDaysPast(14)->getSignedIn();

        $this->assertCount(15, $result['labels']);
    }

    public function test_get_signed_in_supports_the_thirty_one_day_time_frame(): void
    {
        $this->createUserSiteAccessStatistics(['amount_registered' => 1, 'amount_signed_in' => 2, 'created_at' => now()]);

        $result = (new SiteAccessStatisticService())->setAttribute('amount_signed_in')->setDaysPast(31)->getSignedIn();

        $this->assertCount(32, $result['labels']);
    }

    public function test_returns_zero_for_a_time_slot_with_no_statistics(): void
    {
        $result = (new SiteAccessStatisticService())->setAttribute('amount_registered')->setDaysPast(0)->getRegistered();

        $this->assertSame(array_fill(0, 24, 0), $result['data']);
    }
}
