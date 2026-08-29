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
    public function test_end_calls_only_supporting_ender(): void
    {
        $type = new EventType(EventType::WEEKLY_CURRENCY_DROPS);
        $scheduled = new ScheduledEvent();
        $current = new Event();
        $calls = [];

        $raid = Mockery::mock(RaidEventEnderService::class, function (MockInterface $m) use ($type, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)
                ->andReturnUsing(function () use (&$calls): bool {
                    $calls[] = 'raid.supports';

                    return false;
                });
            $m->shouldNotReceive('end');
        });

        $weeklyCurrency = Mockery::mock(WeeklyCurrencyEventEnderService::class, function (MockInterface $m) use ($type, $scheduled, $current, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)
                ->andReturnUsing(function () use (&$calls): bool {
                    $calls[] = 'weekly_currency.supports';

                    return true;
                });
            $m->shouldReceive('end')->once()->with($type, $scheduled, $current)
                ->andReturnUsing(function () use (&$calls): void {
                    $calls[] = 'weekly_currency.end';
                });
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
            $feedback,
        );

        $registry->end($type, $scheduled, $current);

        $this->assertSame([
            'raid.supports',
            'weekly_currency.supports',
            'weekly_currency.end',
        ], $calls);
    }

    public function test_end_does_nothing_when_no_ender_supports(): void
    {
        $type = new EventType(EventType::WINTER_EVENT);
        $scheduled = new ScheduledEvent();
        $current = new Event();
        $calls = [];

        $raid = Mockery::mock(RaidEventEnderService::class, function (MockInterface $m) use ($type, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)->andReturnUsing(function () use (&$calls): bool {
                $calls[] = 'raid.supports';

                return false;
            });
            $m->shouldNotReceive('end');
        });

        $weeklyCurrency = Mockery::mock(WeeklyCurrencyEventEnderService::class, function (MockInterface $m) use ($type, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)->andReturnUsing(function () use (&$calls): bool {
                $calls[] = 'weekly_currency.supports';

                return false;
            });
            $m->shouldNotReceive('end');
        });

        $weeklyCelestials = Mockery::mock(WeeklyCelestialEventEnderService::class, function (MockInterface $m) use ($type, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)->andReturnUsing(function () use (&$calls): bool {
                $calls[] = 'weekly_celestials.supports';

                return false;
            });
            $m->shouldNotReceive('end');
        });

        $weeklyFaction = Mockery::mock(WeeklyFactionLoyaltyEnderService::class, function (MockInterface $m) use ($type, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)->andReturnUsing(function () use (&$calls): bool {
                $calls[] = 'weekly_faction.supports';

                return false;
            });
            $m->shouldNotReceive('end');
        });

        $winter = Mockery::mock(WinterEventEnderService::class, function (MockInterface $m) use ($type, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)->andReturnUsing(function () use (&$calls): bool {
                $calls[] = 'winter.supports';

                return false;
            });
            $m->shouldNotReceive('end');
        });

        $delusional = Mockery::mock(DelusionalMemoriesEventEnderService::class, function (MockInterface $m) use ($type, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)->andReturnUsing(function () use (&$calls): bool {
                $calls[] = 'delusional.supports';

                return false;
            });
            $m->shouldNotReceive('end');
        });

        $feedback = Mockery::mock(FeedbackEventEnderService::class, function (MockInterface $m) use ($type, &$calls) {
            $m->shouldReceive('supports')->once()->with($type)->andReturnUsing(function () use (&$calls): bool {
                $calls[] = 'feedback.supports';

                return false;
            });
            $m->shouldNotReceive('end');
        });

        $registry = new EventEnderRegistry(
            $raid,
            $weeklyCurrency,
            $weeklyCelestials,
            $weeklyFaction,
            $winter,
            $delusional,
            $feedback,
        );

        $registry->end($type, $scheduled, $current);

        $this->assertSame([
            'raid.supports',
            'weekly_currency.supports',
            'weekly_celestials.supports',
            'weekly_faction.supports',
            'winter.supports',
            'delusional.supports',
            'feedback.supports',
        ], $calls);
    }
}
