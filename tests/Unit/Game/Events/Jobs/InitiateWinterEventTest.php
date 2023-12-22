<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Game\Events\Values\EventType;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use App\Game\Events\Jobs\InitiateWinterEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Game\Messages\Events\GlobalMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateScheduledEvent;

class InitiateWinterEventTest extends TestCase {

    use RefreshDatabase, CreateScheduledEvent;

    public function setUp(): void {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        GlobalEventGoal::truncate();
        GlobalEventKill::truncate();
        GlobalEventParticipation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach (Announcement::all() as $announcement) {
            $announcement->delete();
        }

        foreach (ModelsEvent::all() as $event) {
            $event->delete();
        }
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testWinterEventDoesNotTrigger() {
        Event::fake();

        InitiateWinterEvent::dispatch(3);

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
        $this->assertEmpty(ModelsEvent::all());
        $this->assertEmpty(GlobalEventGoal::all());
    }

    public function testWinterEventDoesTrigger() {
        Event::fake();

        $event = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT
        ]);

        InitiateWinterEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
        $this->assertNotEmpty(ModelsEvent::all());
        $this->assertNotEmpty(GlobalEventGoal::all());
    }
}
