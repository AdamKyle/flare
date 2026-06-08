<?php

namespace Tests\Unit\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Core\Controllers\Api\TimersController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class TimersControllerTest extends TestCase
{
    use CreateCharacterAutomation;
    use RefreshDatabase;

    private Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_expired_automation_only_broadcasts_zero_automation_timer(): void
    {
        Event::fake();

        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
        ]);

        (new TimersController)->updateTimersForCharacter($this->character->refresh());

        Event::assertDispatched(AutomationTimeOut::class, function (AutomationTimeOut $event): bool {
            return $event->forLength === 0;
        });
    }

    public function test_expired_older_automation_and_active_latest_automation_broadcasts_active_timer(): void
    {
        Event::fake();

        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
        ]);

        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addSeconds(450),
        ]);

        (new TimersController)->updateTimersForCharacter($this->character->refresh());

        Event::assertDispatched(AutomationTimeOut::class, function (AutomationTimeOut $event): bool {
            return $event->forLength === 450;
        });
    }
}
