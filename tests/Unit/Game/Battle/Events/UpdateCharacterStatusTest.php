<?php

namespace Tests\Unit\Game\Battle\Events;

use App\Flare\Models\Character;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class UpdateCharacterStatusTest extends TestCase
{
    use CreateCharacterAutomation;
    use RefreshDatabase;

    private Character $character;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testPayloadContainsExplorationActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addSeconds(300),
        ]);

        $event = new UpdateCharacterStatus($this->character);

        $this->assertEquals([
            'type' => AutomationType::EXPLORING,
            'name' => 'Exploration',
            'timer_seconds' => 300,
        ], $event->characterStatuses['active_automation']);
    }

    public function testPayloadContainsDelveActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addSeconds(600),
        ]);

        $event = new UpdateCharacterStatus($this->character);

        $this->assertEquals([
            'type' => AutomationType::DELVE,
            'name' => 'Delve',
            'timer_seconds' => 600,
        ], $event->characterStatuses['active_automation']);
    }

    public function testPayloadContainsFactionLoyaltyActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addSeconds(900),
        ]);

        $event = new UpdateCharacterStatus($this->character);

        $this->assertEquals([
            'type' => AutomationType::FACTION_LOYALTY,
            'name' => 'Faction Loyalty',
            'timer_seconds' => 900,
        ], $event->characterStatuses['active_automation']);
    }

    public function testPayloadHasNullActiveAutomationWhenNoAutomationIsRunning(): void
    {
        $event = new UpdateCharacterStatus($this->character);

        $this->assertNull($event->characterStatuses['active_automation']);
        $this->assertEquals(0, $event->characterStatuses['automation_completed_at']);
    }

    public function testPayloadDoesNotDisplayExplorationForUnknownAutomationType(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => 99,
            'completed_at' => now()->addSeconds(300),
        ]);

        $event = new UpdateCharacterStatus($this->character);

        $this->assertNull($event->characterStatuses['active_automation']);
    }

    public function testPayloadHasNullActiveAutomationWhenAutomationIsCompleted(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subSecond(),
        ]);

        $event = new UpdateCharacterStatus($this->character);

        $this->assertNull($event->characterStatuses['active_automation']);
        $this->assertEquals(0, $event->characterStatuses['automation_completed_at']);
    }
}
