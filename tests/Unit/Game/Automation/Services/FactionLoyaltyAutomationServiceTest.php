<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Automation\Jobs\AutomatedFactionLoyalty;
use App\Game\Automation\Services\FactionLoyaltyAutomationService;
use App\Game\Automation\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class FactionLoyaltyAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?FactionLoyaltyAutomationService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character, 1, 1);

        $this->character = $factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->service = resolve(FactionLoyaltyAutomationService::class);
    }

    protected function tearDown(): void
    {
        $this->character = null;
        $this->factionLoyaltyNpc = null;
        $this->service = null;

        parent::tearDown();
    }

    public function test_begin_automation_creates_records_and_dispatches_job(): void
    {
        Event::fake();
        Queue::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, 'attack');

        $automation = CharacterAutomation::where('character_id', $this->character->id)
            ->where('type', AutomationType::FACTION_LOYALTY->value)
            ->first();

        $this->assertNotNull($automation);
        $this->assertSame('attack', $automation->attack_type);
        $this->assertTrue(FactionLoyaltyAutomation::where('character_automation_id', $automation->id)->exists());
        $this->assertFalse($this->character->fresh()->can_craft);

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_stop_automation_returns_error_when_nothing_is_running(): void
    {
        $result = $this->service->stopAutomation($this->character);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Nope. You don\'t own that.', $result['message']);
    }

    public function test_stop_automation_removes_the_running_automation(): void
    {
        Event::fake();
        Queue::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, 'attack');
        $character = $this->character->fresh();

        $result = $this->service->stopAutomation($character);

        $this->assertSame(200, $result['status']);
        $this->assertFalse(
            CharacterAutomation::where('character_id', $character->id)
                ->where('type', AutomationType::FACTION_LOYALTY->value)
                ->where('completed_at', '>', now())
                ->exists()
        );
        $this->assertTrue($character->fresh()->can_craft);
    }
}
