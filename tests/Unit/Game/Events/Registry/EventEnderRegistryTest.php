<?php

namespace Tests\Unit\Game\Events\Registry;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Registry\EventEnderRegistry;
use App\Game\Events\Services\DelusionalMemoriesEventEnderService;
use App\Game\Events\Services\FeedbackEventEnderService;
use App\Game\Events\Services\RaidEventEnderService;
use App\Game\Events\Services\WeeklyCelestialEventEnderService;
use App\Game\Events\Services\WeeklyCurrencyEventEnderService;
use App\Game\Events\Services\WeeklyFactionLoyaltyEnderService;
use App\Game\Events\Services\WinterEventEnderService;
use App\Game\Events\Values\EventType;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class EventEnderRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_end_calls_only_supporting_ender(): void
    {
        $raid = Mockery::mock(RaidEventEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(false);
            $m->shouldNotReceive('end');
        });
        $weeklyCurrency = Mockery::mock(WeeklyCurrencyEventEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(true);
            $m->shouldReceive('end')->once()->with(
                Mockery::type(EventType::class),
                Mockery::type(ScheduledEvent::class),
                Mockery::type(Event::class)
            );
        });
        $weeklyCelestials = Mockery::mock(WeeklyCelestialEventEnderService::class, function (MockInterface $m) {
            $m->shouldNotReceive('supports');
            $m->shouldNotReceive('end');
        });
        $weeklyFaction = Mockery::mock(WeeklyFactionLoyaltyEnderService::class, function (MockInterface $m) {
            $m->shouldNotReceive('supports');
            $m->shouldNotReceive('end');
        });
        $winter = Mockery::mock(WinterEventEnderService::class, function (MockInterface $m) {
            $m->shouldNotReceive('supports');
            $m->shouldNotReceive('end');
        });
        $delusional = Mockery::mock(DelusionalMemoriesEventEnderService::class, function (MockInterface $m) {
            $m->shouldNotReceive('supports');
            $m->shouldNotReceive('end');
        });
        $feedback = Mockery::mock(FeedbackEventEnderService::class, function (MockInterface $m) {
            $m->shouldNotReceive('supports');
            $m->shouldNotReceive('end');
        });

        $registry = new EventEnderRegistry(
            $raid,
            $weeklyCurrency,
            $weeklyCelestials,
            $weeklyFaction,
            $winter,
            $delusional,
            $feedback
        );

        $type = new EventType(EventType::WEEKLY_CURRENCY_DROPS);
        $scheduled = new ScheduledEvent();
        $current = new Event();

        $registry->end($type, $scheduled, $current);

        $this->assertTrue(true);
    }

    public function test_end_does_nothing_when_no_ender_supports(): void
    {
        $raid = Mockery::mock(RaidEventEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(false);
            $m->shouldNotReceive('end');
        });
        $weeklyCurrency = Mockery::mock(WeeklyCurrencyEventEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(false);
            $m->shouldNotReceive('end');
        });
        $weeklyCelestials = Mockery::mock(WeeklyCelestialEventEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(false);
            $m->shouldNotReceive('end');
        });
        $weeklyFaction = Mockery::mock(WeeklyFactionLoyaltyEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(false);
            $m->shouldNotReceive('end');
        });
        $winter = Mockery::mock(WinterEventEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(false);
            $m->shouldNotReceive('end');
        });
        $delusional = Mockery::mock(DelusionalMemoriesEventEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(false);
            $m->shouldNotReceive('end');
        });
        $feedback = Mockery::mock(FeedbackEventEnderService::class, function (MockInterface $m) {
            $m->shouldReceive('supports')->once()->andReturn(false);
            $m->shouldNotReceive('end');
        });

        $registry = new EventEnderRegistry(
            $raid,
            $weeklyCurrency,
            $weeklyCelestials,
            $weeklyFaction,
            $winter,
            $delusional,
            $feedback
        );

        $type = new EventType(EventType::WINTER_EVENT);
        $scheduled = new ScheduledEvent();
        $current = new Event();

        $registry->end($type, $scheduled, $current);

        $this->assertTrue(true);
    }
}
