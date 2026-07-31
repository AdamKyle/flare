<?php

namespace Tests\Unit\Game\Core\Services;

use App\Game\Core\Services\GameTimerService;
use Carbon\Carbon;
use Tests\TestCase;

class GameTimerServiceTest extends TestCase
{
    private GameTimerService $gameTimerService;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-02 12:00:00'));

        $this->gameTimerService = new GameTimerService();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_production_leaves_two_hours_unchanged(): void
    {
        $this->setApplicationEnvironment('production');

        $this->assertSame(7200, $this->gameTimerService->seconds(7200));
        $this->assertSame('2026-07-02 14:00:00', $this->gameTimerService->availableAtFromHours(2)->toDateTimeString());
    }

    public function test_staging_leaves_two_hours_unchanged(): void
    {
        $this->setApplicationEnvironment('staging');

        $this->assertSame(7200, $this->gameTimerService->seconds(7200));
        $this->assertSame('2026-07-02 14:00:00', $this->gameTimerService->availableAtFromHours(2)->toDateTimeString());
    }

    public function test_development_caps_two_hours_to_one_minute(): void
    {
        $this->setApplicationEnvironment('development');

        $this->assertSame(60, $this->gameTimerService->seconds(7200));
        $this->assertSame('2026-07-02 12:01:00', $this->gameTimerService->availableAtFromHours(2)->toDateTimeString());
    }

    public function test_development_caps_fifteen_minutes_to_one_minute(): void
    {
        $this->setApplicationEnvironment('development');

        $this->assertSame(60, $this->gameTimerService->seconds(900));
        $this->assertSame('2026-07-02 12:01:00', $this->gameTimerService->availableAtFromMinutes(15)->toDateTimeString());
    }

    public function test_development_leaves_sixty_seconds_unchanged(): void
    {
        $this->setApplicationEnvironment('development');

        $this->assertSame(60, $this->gameTimerService->seconds(60));
        $this->assertSame('2026-07-02 12:01:00', $this->gameTimerService->availableAtFromSeconds(60)->toDateTimeString());
    }

    public function test_development_leaves_thirty_seconds_unchanged(): void
    {
        $this->setApplicationEnvironment('development');

        $this->assertSame(30, $this->gameTimerService->seconds(30));
        $this->assertSame('2026-07-02 12:00:30', $this->gameTimerService->availableAtFromSeconds(30)->toDateTimeString());
    }

    public function test_disabled_cap_leaves_duration_unchanged(): void
    {
        $this->setApplicationEnvironment('development');
        config(['game_timers.development_cap.enabled' => false]);

        $this->assertSame(7200, $this->gameTimerService->seconds(7200));
        $this->assertSame('2026-07-02 14:00:00', $this->gameTimerService->availableAtFromHours(2)->toDateTimeString());
    }

    public function test_custom_development_max_seconds_is_used(): void
    {
        $this->setApplicationEnvironment('development');
        config(['game_timers.development_cap.max_seconds' => 120]);

        $this->assertSame(120, $this->gameTimerService->seconds(7200));
        $this->assertSame('2026-07-02 12:02:00', $this->gameTimerService->availableAtFromHours(2)->toDateTimeString());
    }

    private function setApplicationEnvironment(string $environment): void
    {
        $this->app['env'] = $environment;
    }
}
