<?php

namespace Tests\Unit\Game\Battle\Events;

use App\Flare\Models\Character;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class UpdateCharacterStatusTest extends TestCase
{
    use CreateCharacterAutomation, RefreshDatabase;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        $this->character = null;

        parent::tearDown();
    }

    public function test_can_attack_again_at_uses_the_precise_cooldown_override(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character->update([
            'can_attack' => false,
            'can_attack_again_at' => now()->addSeconds(10),
        ]);

        $event = new UpdateCharacterStatus($this->character->refresh(), attackCooldownSecondsOverride: 7.2);

        $this->assertSame(7.2, $event->characterStatuses['can_attack_again_at']);
    }

    public function test_can_attack_again_at_ignores_the_override_once_the_cooldown_is_cleared(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character->update([
            'can_attack' => true,
            'can_attack_again_at' => null,
        ]);

        $event = new UpdateCharacterStatus($this->character->refresh(), attackCooldownSecondsOverride: 7.2);

        $this->assertSame(0.0, $event->characterStatuses['can_attack_again_at']);
    }

    public function test_can_attack_again_at_resolves_to_zero_when_no_cooldown_is_set(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character->update([
            'can_attack' => true,
            'can_attack_again_at' => null,
        ]);

        $event = new UpdateCharacterStatus($this->character->refresh());

        $this->assertSame(0.0, $event->characterStatuses['can_attack_again_at']);
    }

    public function test_can_attack_again_at_never_reports_a_negative_duration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $this->character->update([
            'is_dead' => true,
            'can_attack' => false,
            'can_attack_again_at' => now()->subSeconds(2),
        ]);

        $event = new UpdateCharacterStatus($this->character->refresh());

        $this->assertSame(0.0, $event->characterStatuses['can_attack_again_at']);
    }

    public function test_active_automation_reports_the_newest_active_automation_when_multiple_exist(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addHour(),
        ]);

        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $event = new UpdateCharacterStatus($this->character->refresh());

        $this->assertSame('Delve', $event->characterStatuses['active_automation']['name']);
    }
}
