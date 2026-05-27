<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\AutomationType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class CharacterSheetBaseInfoTransformerTest extends TestCase
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

    public function testTransformContainsExplorationActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addSeconds(300),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertEquals([
            'type' => AutomationType::EXPLORING,
            'name' => 'Exploration',
            'timer_seconds' => 300,
        ], $data['active_automation']);
    }

    public function testTransformContainsDelveActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addSeconds(600),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertEquals([
            'type' => AutomationType::DELVE,
            'name' => 'Delve',
            'timer_seconds' => 600,
        ], $data['active_automation']);
    }

    public function testTransformContainsFactionLoyaltyActiveAutomationMetadata(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addSeconds(900),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertEquals([
            'type' => AutomationType::FACTION_LOYALTY,
            'name' => 'Faction Loyalty',
            'timer_seconds' => 900,
        ], $data['active_automation']);
    }

    public function testTransformHasNullActiveAutomationWhenNoAutomationIsRunning(): void
    {
        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertNull($data['active_automation']);
        $this->assertEquals(0, $data['automation_completed_at']);
    }

    public function testTransformDoesNotDisplayExplorationForUnknownAutomationType(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => 99,
            'completed_at' => now()->addSeconds(300),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertNull($data['active_automation']);
    }

    public function testTransformHasNullActiveAutomationWhenAutomationIsCompleted(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subSecond(),
        ]);

        $data = resolve(CharacterSheetBaseInfoTransformer::class)->transform($this->character);

        $this->assertNull($data['active_automation']);
        $this->assertEquals(0, $data['automation_completed_at']);
    }
}
