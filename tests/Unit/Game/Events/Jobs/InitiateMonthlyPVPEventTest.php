<?php

namespace Tests\Unit\Game\Events\Jobs;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event as ModelsEvent;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Events\Jobs\InitiateMonthlyPVPEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateScheduledEvent;

class InitiateMonthlyPVPEventTest extends TestCase {

    use RefreshDatabase, CreateScheduledEvent;

    private ?CharacterFactory $character;

    public function setUp(): void {
        parent::setUp();

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

    public function testMonthlyPVPEventDoesNotInitiate() {
        Event::fake();

        InitiateMonthlyPVPEvent::dispatch(3);

        Event::assertNotDispatched(GlobalMessageEvent::class);
        $this->assertEmpty(Announcement::all());
    }

    public function testInitiateMonthlyPVP() {
        Event::fake();

        $event = $this->createScheduledEvent();

        InitiateMonthlyPVPEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        $this->assertNotEmpty(Announcement::all());
    }

    public function testInitiateMonthlyPVPWhenUsersOnLine() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $character->update(['level' => 302]);

        $character = $character->refresh();

        DB::table('sessions')->truncate();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->refresh()->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity' => 1602801731,
        ]]);

        $event = $this->createScheduledEvent();

        InitiateMonthlyPVPEvent::dispatch($event->id);

        Event::assertDispatched(GlobalMessageEvent::class);
        Event::assertDispatched(UpdateCharacterStatus::class);
        $this->assertNotEmpty(Announcement::all());
    }
}
