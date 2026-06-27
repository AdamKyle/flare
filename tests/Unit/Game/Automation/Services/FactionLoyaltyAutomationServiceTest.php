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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
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

    protected function setUp(): void
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

    protected function tearDown(): void
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

    public function test_begin_automation_creates_character_automation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $characterAutomation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals($this->character->id, $characterAutomation->character_id);
        $this->assertEquals(AutomationType::FACTION_LOYALTY, $characterAutomation->type);
        $this->assertEquals(AttackTypeValue::ATTACK, $characterAutomation->attack_type);
    }

    public function test_begin_automation_sets_completed_at_eight_hours_from_now(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $characterAutomation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals($now->copy()->addHours(8)->toDateTimeString(), $characterAutomation->completed_at->toDateTimeString());
    }

    public function test_begin_automation_creates_faction_loyalty_automation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $factionLoyaltyAutomation = FactionLoyaltyAutomation::query()->latest('id')->first();

        $this->assertEquals($this->character->id, $factionLoyaltyAutomation->character_id);
        $this->assertEquals($this->factionLoyaltyNpc->id, $factionLoyaltyAutomation->faction_loyalty_npc_id);
    }

    public function test_begin_automation_links_faction_loyalty_automation_to_character_automation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $characterAutomation = CharacterAutomation::query()->latest('id')->first();
        $factionLoyaltyAutomation = FactionLoyaltyAutomation::query()->latest('id')->first();

        $this->assertEquals($characterAutomation->id, $factionLoyaltyAutomation->character_automation_id);
    }

    public function test_begin_automation_disables_crafting(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $this->assertFalse($this->character->refresh()->can_craft);
    }

    public function test_begin_automation_clears_crafting_cooldown(): void
    {
        Queue::fake();
        Event::fake();

        $this->character->update([
            'can_craft_again_at' => now()->addHour(),
        ]);

        $this->service->beginAutomation($this->character->refresh(), $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        $this->assertNull($this->character->refresh()->can_craft_again_at);
    }

    public function test_begin_automation_dispatches_update_character_status(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function test_begin_automation_dispatches_update_character_status_with_faction_loyalty_automation_running(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Event::assertDispatched(UpdateCharacterStatus::class, function (UpdateCharacterStatus $event): bool {
            return $event->characterStatuses['is_faction_loyalty_automation_running'] === true;
        });
    }

    public function test_begin_automation_dispatches_automation_log_update(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function test_begin_automation_dispatches_automation_time_out(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function test_begin_automation_dispatches_automated_faction_loyalty_job(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_begin_automation_dispatches_automated_faction_loyalty_job_with_expected_ids(): void
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

    public function test_begin_automation_delays_automated_faction_loyalty_job_by_time_delay(): void
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

    public function test_begin_automation_dispatches_automated_faction_loyalty_job_on_faction_loyalty_queue_with_long_running_connection(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);

        Queue::assertPushed(AutomatedFactionLoyalty::class, function (AutomatedFactionLoyalty $job): bool {
            return $job->queue === 'faction_loyalty' && $job->connection === 'long_running';
        });
    }

    public function test_begin_automation_logs_dispatch_requested_before_dispatch_completed(): void
    {
        Queue::fake();
        Event::fake();

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->withAnyArgs()->zeroOrMoreTimes()->byDefault();
        Log::shouldReceive('info')
            ->once()
            ->with('Faction loyalty automation dispatch requested.', Mockery::type('array'))
            ->ordered();
        Log::shouldReceive('info')
            ->once()
            ->with('Faction loyalty automation dispatch completed.', Mockery::type('array'))
            ->ordered();

        $this->service->beginAutomation($this->character, $this->factionLoyaltyNpc, AttackTypeValue::ATTACK);
    }

    public function test_stop_automation_deletes_character_automation(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->characterAutomation = $this->factionLoyaltyFactory->getCharacterAutomation();

        $this->service->stopAutomation($this->character);

        $this->assertNull($this->characterAutomation->fresh());
    }

    public function test_stop_automation_completes_faction_loyalty_automation(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();

        $this->service->stopAutomation($this->character);

        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_stop_automation_completes_faction_loyalty_automation_linked_to_exact_active_character_automation(): void
    {
        Event::fake();

        $olderCharacterAutomation = CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHours(7),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $olderFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $olderCharacterAutomation->id,
            'character_id' => $this->character->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'started_at' => now()->subMinute(),
        ]);
        $newerCharacterAutomation = CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $newerFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $newerCharacterAutomation->id,
            'character_id' => $this->character->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'started_at' => now(),
        ]);

        $this->service->stopAutomation($this->character);

        $this->assertNotNull($newerFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertNull($olderFactionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_stop_automation_does_not_delete_unrelated_current_automation_records(): void
    {
        Event::fake();

        $unrelatedAutomation = CharacterAutomation::create([
            'character_id' => $this->character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHours(8),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        $this->assertNotNull($unrelatedAutomation->fresh());
    }

    public function test_stop_automation_re_enables_crafting(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->character->update([
            'can_craft' => false,
        ]);

        $this->service->stopAutomation($this->character->refresh());

        $this->assertTrue($this->character->refresh()->can_craft);
    }

    public function test_stop_automation_clears_crafting_cooldown(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->character->update([
            'can_craft_again_at' => now()->addHour(),
        ]);

        $this->service->stopAutomation($this->character->refresh());

        $this->assertNull($this->character->refresh()->can_craft_again_at);
    }

    public function test_stop_automation_returns_success_result(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $result = $this->service->stopAutomation($this->character);

        $this->assertEquals(200, $result['status']);
    }

    public function test_stop_automation_returns_error_when_no_character_automation_exists(): void
    {
        Event::fake();

        $result = $this->service->stopAutomation($this->character);

        $this->assertEquals(422, $result['status']);
    }

    public function test_stop_automation_clears_character_sheet_cache(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        Cache::put('character-sheet-'.$this->character->id, ['level' => 1]);

        $this->service->stopAutomation($this->character);

        $this->assertFalse(Cache::has('character-sheet-'.$this->character->id));
    }

    public function test_stop_automation_clears_character_defence_cache(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        Cache::put('character-defence-'.$this->character->id, 100);

        $this->service->stopAutomation($this->character);

        $this->assertFalse(Cache::has('character-defence-'.$this->character->id));
    }

    public function test_stop_automation_dispatches_automation_time_out(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function test_stop_automation_dispatches_automation_status(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(AutomationStatus::class);
    }

    public function test_stop_automation_dispatches_update_character_status(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function test_stop_automation_dispatches_update_character_status_without_faction_loyalty_automation_running(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(UpdateCharacterStatus::class, function (UpdateCharacterStatus $event): bool {
            return $event->characterStatuses['is_faction_loyalty_automation_running'] === false;
        });
    }

    public function test_stop_automation_dispatches_automation_log_update(): void
    {
        Event::fake();

        $this->factionLoyaltyFactory->createAutomation();

        $this->service->stopAutomation($this->character);

        Event::assertDispatched(AutomationLogUpdate::class);
    }
}
