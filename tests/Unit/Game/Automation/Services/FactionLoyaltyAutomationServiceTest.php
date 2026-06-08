<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Jobs\AutomatedFactionLoyalty;
use App\Game\Automation\Services\FactionLoyaltyAutomationService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class FactionLoyaltyAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?FactionLoyaltyAutomationService $service = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?Character $character = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?CharacterAutomation $characterAutomation = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(FactionLoyaltyAutomationService::class);

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character);

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        $this->service = null;
        $this->factionLoyaltyFactory = null;
        $this->character = null;
        $this->factionLoyaltyNpc = null;
        $this->characterAutomation = null;
        $this->factionLoyaltyAutomation = null;

        parent::tearDown();
    }

    public function testBeginAutomationCreatesCharacterAutomation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $characterAutomation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals($this->character->id, $characterAutomation->character_id);
        $this->assertEquals(AutomationType::FACTION_LOYALTY, $characterAutomation->type);
        $this->assertEquals(AttackTypeValue::ATTACK, $characterAutomation->attack_type);
    }

    public function testBeginAutomationSetsCompletedAtEightHoursFromNow(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $characterAutomation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals($now->copy()->addHours(8)->toDateTimeString(), $characterAutomation->completed_at->toDateTimeString());
    }

    public function testBeginAutomationCreatesFactionLoyaltyAutomation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $factionLoyaltyAutomation = FactionLoyaltyAutomation::query()->latest('id')->first();

        $this->assertEquals($this->character->id, $factionLoyaltyAutomation->character_id);
        $this->assertEquals($this->factionLoyaltyNpc->id, $factionLoyaltyAutomation->faction_loyalty_npc_id);
    }

    public function testBeginAutomationLinksFactionLoyaltyAutomationToCharacterAutomation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $characterAutomation = CharacterAutomation::query()->latest('id')->first();
        $factionLoyaltyAutomation = FactionLoyaltyAutomation::query()->latest('id')->first();

        $this->assertEquals($characterAutomation->id, $factionLoyaltyAutomation->character_automation_id);
    }

    public function testBeginAutomationDisablesCrafting(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $this->assertFalse($this->character->refresh()->can_craft);
    }

    public function testBeginAutomationClearsCraftingCooldown(): void
    {
        Queue::fake();
        Event::fake();

        $this->character->update([
            'can_craft_again_at' => now()->addHour(),
        ]);

        $this->service->beginAutomation($this->character->refresh(), $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $this->assertNull($this->character->refresh()->can_craft_again_at);
    }

    public function testBeginAutomationDispatchesUpdateCharacterStatus(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testBeginAutomationDispatchesUpdateCharacterStatusWithFactionLoyaltyAutomationRunning(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Event::assertDispatched(UpdateCharacterStatus::class, function (UpdateCharacterStatus $event): bool {
            return $event->characterStatuses['is_faction_loyalty_automation_running'] === true;
        });
    }

    public function testBeginAutomationDispatchesAutomationLogUpdate(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testBeginAutomationDispatchesAutomationTimeOut(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testBeginAutomationDispatchesAutomatedFactionLoyaltyJob(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function testBeginAutomationDispatchesAutomatedFactionLoyaltyJobWithExpectedIds(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $characterAutomation = CharacterAutomation::query()->latest('id')->first();
        $factionLoyaltyAutomation = FactionLoyaltyAutomation::query()->latest('id')->first();

        Queue::assertPushed(AutomatedFactionLoyalty::class, function (AutomatedFactionLoyalty $job) use ($characterAutomation, $factionLoyaltyAutomation): bool {
            return $job->characterId === $this->character->id
                && $job->automationId === $characterAutomation->id
                && $job->factionLoyaltyAutomationId === $factionLoyaltyAutomation->id
                && $job->timeDelay === FactionLoyaltyAutomationService::TIME_DELAY;
        });
    }

    public function testBeginAutomationDelaysAutomatedFactionLoyaltyJobByTimeDelay(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Queue::assertPushed(AutomatedFactionLoyalty::class, function (AutomatedFactionLoyalty $job) use ($now): bool {
            return $job->delay->toDateTimeString() === $now->copy()->addMinutes(FactionLoyaltyAutomationService::TIME_DELAY)->toDateTimeString();
        });
    }

    public function testBeginAutomationDispatchesAutomatedFactionLoyaltyJobOnFactionLoyaltyQueueWithLongRunningConnection(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Queue::assertPushed(AutomatedFactionLoyalty::class, function (AutomatedFactionLoyalty $job): bool {
            return $job->queue === 'faction_loyalty' && $job->connection === 'long_running';
        });
    }

    public function testStopAutomationDeletesCharacterAutomation(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->characterAutomation = $this->factionLoyaltyFactory->getCharacterAutomation();

        $this->service->stopAutomation($this->character);

        $this->assertNull($this->characterAutomation->fresh());
    }

    public function testStopAutomationCompletesFactionLoyaltyAutomation(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $this->service->stopAutomation($this->character);

        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function testStopAutomationReEnablesCrafting(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->character->update([
            'can_craft' => false,
        ]);

        $this->service->stopAutomation($this->character->refresh());

        $this->assertTrue($this->character->refresh()->can_craft);
    }

    public function testStopAutomationClearsCraftingCooldown(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->character->update([
            'can_craft_again_at' => now()->addHour(),
        ]);

        $this->service->stopAutomation($this->character->refresh());

        $this->assertNull($this->character->refresh()->can_craft_again_at);
    }

    public function testStopAutomationReturnsSuccessResult(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $result = $this->service->stopAutomation($this->character);

        $this->assertEquals(200, $result['status']);
    }

    public function testStopAutomationReturnsErrorWhenNoCharacterAutomationExists(): void
    {
        Event::fake();

        $result = $this->service->stopAutomation($this->character);

        $this->assertEquals(422, $result['status']);
    }

    public function testStopAutomationClearsCharacterSheetCache(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        Cache::put('character-sheet-' . $this->character->id, ['level' => 1]);

        $this->service->stopAutomation($this->character);

        $this->assertFalse(Cache::has('character-sheet-' . $this->character->id));
    }

    public function testStopAutomationClearsCharacterDefenceCache(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        Cache::put('character-defence-' . $this->character->id, 100);

        $this->service->stopAutomation($this->character);

        $this->assertFalse(Cache::has('character-defence-' . $this->character->id));
    }

    public function testStopAutomationDispatchesAutomationTimeOut(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testStopAutomationDispatchesAutomationStatus(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(AutomationStatus::class);
    }

    public function testStopAutomationDispatchesUpdateCharacterStatus(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testStopAutomationDispatchesUpdateCharacterStatusWithoutFactionLoyaltyAutomationRunning(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(UpdateCharacterStatus::class, function (UpdateCharacterStatus $event): bool {
            return $event->characterStatuses['is_faction_loyalty_automation_running'] === false;
        });
    }

    public function testStopAutomationDispatchesAutomationLogUpdate(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(AutomationLogUpdate::class);
    }
}
