<?php

namespace Tests\Console\Events;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Registry\EventEnderRegistry;
use App\Game\Events\Services\ScheduleEventFinalizerService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateScheduledEvent;

class EndScheduledEventTest extends TestCase
{
    use RefreshDatabase, CreateScheduledEvent, CreateEvent;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testReturnsEarlyWhenNoScheduledEvents(): void
    {
        $registry = Mockery::mock(EventEnderRegistry::class);
        $registry->shouldNotReceive('end');
        $this->instance(EventEnderRegistry::class, $registry);

        $finalizer = Mockery::mock(ScheduleEventFinalizerService::class);
        $finalizer->shouldNotReceive('markNotRunningAndBroadcast');
        $this->instance(ScheduleEventFinalizerService::class, $finalizer);

        $this->artisan('end:scheduled-event');
        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function testFinalizesWhenNoCurrentEvent(): void
    {
        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'end_date' => now()->subMinute(),
            'currently_running' => true,
        ]);

        $registry = Mockery::mock(EventEnderRegistry::class);
        $registry->shouldNotReceive('end');
        $this->instance(EventEnderRegistry::class, $registry);

        $finalizer = Mockery::mock(ScheduleEventFinalizerService::class, function (MockInterface $m) use ($scheduled) {
            $m->shouldReceive('markNotRunningAndBroadcast')->once()->with(Mockery::on(function ($arg) use ($scheduled) {
                return $arg instanceof ScheduledEvent && $arg->id === $scheduled->id;
            }));
        });
        $this->instance(ScheduleEventFinalizerService::class, $finalizer);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(1, ScheduledEvent::count());
        $this->assertEquals($scheduled->id, ScheduledEvent::first()->id);
    }

    public function testEndsViaRegistryThenFinalizes(): void
    {
        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'end_date' => now()->subMinute(),
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'started_at' => now()->subHours(2),
            'ends_at' => now()->subMinute(),
        ]);

        $registry = Mockery::mock(EventEnderRegistry::class, function (MockInterface $m) use ($scheduled, $current) {
            $m->shouldReceive('end')->once()->with(
                Mockery::on(function ($type) {
                    return $type instanceof EventType;
                }),
                Mockery::on(function ($sched) use ($scheduled) {
                    return $sched instanceof ScheduledEvent && $sched->id === $scheduled->id;
                }),
                Mockery::on(function ($evt) use ($current) {
                    return $evt instanceof Event && $evt->id === $current->id;
                })
            );
        });
        $this->instance(EventEnderRegistry::class, $registry);

        $finalizer = Mockery::mock(ScheduleEventFinalizerService::class, function (MockInterface $m) use ($scheduled) {
            $m->shouldReceive('markNotRunningAndBroadcast')->once()->with(Mockery::on(function ($arg) use ($scheduled) {
                return $arg instanceof ScheduledEvent && $arg->id === $scheduled->id;
            }));
        });
        $this->instance(ScheduleEventFinalizerService::class, $finalizer);

        $this->artisan('end:scheduled-event');

        $this->assertEquals($scheduled->id, ScheduledEvent::first()->id);
        $this->assertEquals($current->id, Event::first()->id);
    }
}
