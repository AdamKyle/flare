<?php

namespace Tests\Unit\Game\Automation\FactionLoyalty\Jobs;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\Session;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\FactionLoyalty\Coordinators\FactionLoyaltyAutomationActionCoordinator;
use App\Game\Automation\FactionLoyalty\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\FactionLoyalty\Enums\AutomatedCraftingResultType;
use App\Game\Automation\FactionLoyalty\Enums\AutomatedFightResultType;
use App\Game\Automation\FactionLoyalty\Enums\FactionLoyaltyCoordinatorAction;
use App\Game\Automation\FactionLoyalty\Handlers\AutomatedBountyFightHandler;
use App\Game\Automation\FactionLoyalty\Handlers\AutomatedCraftingHandler;
use App\Game\Automation\FactionLoyalty\Jobs\AutomatedFactionLoyalty;
use App\Game\Automation\FactionLoyalty\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\FactionLoyalty\Loggers\FactionLoyaltyAutomationFightLogger;
use App\Game\Automation\FactionLoyalty\Values\AutomatedCraftingResult;
use App\Game\Automation\FactionLoyalty\Values\AutomatedFightResult;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Exceptions\MissingInventoryException;
use App\Game\Core\Combat\Values\AttackType;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateFactionLoyaltyAutomation;

class AutomatedFactionLoyaltyTest extends TestCase
{
    use CreateCharacterAutomation, CreateFactionLoyaltyAutomation, RefreshDatabase;

    public function test_missing_exact_character_automation_does_not_delete_newer_active_faction_loyalty_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $newerCharacterAutomation = $factory->getCharacterAutomation();
        $newerFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $character->update(['can_craft' => false]);
        AutomatedFactionLoyalty::dispatch($character->id, $newerCharacterAutomation->id + 1000, $newerFactionLoyaltyAutomation->id + 1000, 1);

        $this->assertNotNull($newerCharacterAutomation->fresh());
        $this->assertNull($newerFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
    }

    public function test_missing_exact_faction_loyalty_automation_does_not_delete_newer_active_faction_loyalty_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $newerCharacterAutomation = $factory->getCharacterAutomation();
        $newerFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $staleCharacterAutomation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);
        $character->update(['can_craft' => false]);
        AutomatedFactionLoyalty::dispatch($character->id, $staleCharacterAutomation->id, $newerFactionLoyaltyAutomation->id + 1000, 1);

        $this->assertNotNull($newerCharacterAutomation->fresh());
        $this->assertNull($newerFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
    }

    public function test_completed_exact_faction_loyalty_automation_does_not_delete_newer_active_faction_loyalty_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $newerCharacterAutomation = $factory->getCharacterAutomation();
        $newerFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $staleCharacterAutomation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);
        $completedFactionLoyaltyAutomation = $this->createFactionLoyaltyAutomation([
            'character_automation_id' => $staleCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
        $character->update(['can_craft' => false]);
        AutomatedFactionLoyalty::dispatch($character->id, $staleCharacterAutomation->id, $completedFactionLoyaltyAutomation->id, 1);

        $this->assertNotNull($newerCharacterAutomation->fresh());
        $this->assertNull($newerFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
    }

    public function test_expired_exact_character_automation_ends_only_exact_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $exactCharacterAutomation = $factory->getCharacterAutomation();
        $exactFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $exactCharacterAutomation->update(['completed_at' => now()->subSecond()]);
        $unrelatedAutomation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);
        AutomatedFactionLoyalty::dispatch($character->id, $exactCharacterAutomation->id, $exactFactionLoyaltyAutomation->id, 1);

        $this->assertNull($exactCharacterAutomation->fresh());
        $this->assertNotNull($exactFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertNotNull($unrelatedAutomation->fresh());
    }

    public function test_stale_job_returns_before_resolving_npc_or_action(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);
        $oldFactionLoyaltyAutomation = $this->createFactionLoyaltyAutomation([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
            'completed_at' => null,
        ]);
        $factory->createAutomation();
        $newerCharacterAutomation = $factory->getCharacterAutomation();
        $newerFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $character->update(['can_craft' => false]);
        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldNotReceive('setUp');
        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldNotReceive('setUp');
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1);

        $this->assertNotNull($oldCharacterAutomation->fresh());
        $this->assertNull($oldFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertNotNull($newerCharacterAutomation->fresh());
        $this->assertNull($newerFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
    }

    public function test_exception_handling_does_not_alter_automation_state_when_newer_active_automation_exists(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $oldCharacterAutomation = $factory->getCharacterAutomation();
        $oldFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $factionLoyaltyNpc = $factory->getAssistingFactionLoyaltyNpc();
        $itemId = $factory->getCraftingItemsForNpc($factionLoyaltyNpc)[0]->id;
        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($factionLoyaltyNpc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $itemId],
        ]);
        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->once()->andReturnSelf();
        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $craftingHandler->shouldReceive('handle')
            ->once()
            ->andReturnUsing(function () use ($factory): void {
                $factory->createAutomation();

                throw new Exception('Crafting failed.');
            });
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);
        $character->update(['can_craft' => false]);

        AutomatedFactionLoyalty::dispatch($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1);

        $this->assertNotNull($oldCharacterAutomation->fresh());
        $this->assertNull($oldFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertNotNull($factory->getCharacterAutomation()->fresh());
        $this->assertNull($factory->getFactionLoyaltyAutomation()->refresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
    }

    public function test_failed_logs_and_cleans_up_only_exact_owned_automation(): void
    {
        Event::fake();
        Log::shouldReceive('channel')->once()->with('faction_loyalty')->andReturnSelf();
        Log::shouldReceive('error')
            ->twice()
            ->with('Faction loyalty automation job failed.', Mockery::type('array'));

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $characterAutomation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $character->update(['can_craft' => false]);
        $job = new AutomatedFactionLoyalty($character->id, $characterAutomation->id, $factionLoyaltyAutomation->id, 1);

        $job->failed(new Exception('Job failed.'));

        $this->assertNull($characterAutomation->fresh());
        $this->assertNotNull($factionLoyaltyAutomation->refresh()->completed_at);
        $this->assertTrue($character->refresh()->can_craft);
        $this->assertSame(1, FactionLoyaltyAutomationWarning::where(
            'faction_loyalty_automation_id',
            $factionLoyaltyAutomation->id,
        )->count());
        Event::assertDispatched(UpdateCharacterStatus::class);
        Event::assertDispatched(AutomationTimeOut::class);
        Event::assertDispatched(AutomationStatus::class);
    }

    public function test_failed_cleanup_does_not_create_duplicate_warnings(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $characterAutomation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $job = new AutomatedFactionLoyalty(
            $character->id,
            $characterAutomation->id,
            $factionLoyaltyAutomation->id,
            1,
        );

        $job->failed(new Exception('Job failed.'));
        $job->failed(new Exception('Job failed again.'));

        $this->assertSame(1, FactionLoyaltyAutomationWarning::where(
            'faction_loyalty_automation_id',
            $factionLoyaltyAutomation->id,
        )->count());
        $this->assertTrue($character->refresh()->can_craft);
    }

    public function test_failed_does_not_alter_newer_active_automation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $staleCharacterAutomation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);
        $staleFactionLoyaltyAutomation = $this->createFactionLoyaltyAutomation([
            'character_automation_id' => $staleCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
            'completed_at' => null,
        ]);
        $factory->createAutomation();
        $newerCharacterAutomation = $factory->getCharacterAutomation();
        $newerFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $character->update(['can_craft' => false]);
        $job = new AutomatedFactionLoyalty($character->id, $staleCharacterAutomation->id, $staleFactionLoyaltyAutomation->id, 1);

        $job->failed(new Exception('Job failed.'));

        $this->assertNotNull($staleCharacterAutomation->fresh());
        $this->assertNull($staleFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertNotNull($newerCharacterAutomation->fresh());
        $this->assertNull($newerFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
    }

    public function test_failed_stale_job_logs_and_returns_without_cleanup(): void
    {
        Log::shouldReceive('channel')->twice()->with('faction_loyalty')->andReturnSelf();
        Log::shouldReceive('error')
            ->twice()
            ->with('Faction loyalty automation job failed.', Mockery::type('array'));
        Log::shouldReceive('warning')
            ->twice()
            ->with('Faction loyalty stale failed-job cleanup skipped because a newer active automation exists.', Mockery::on(function (array $context): bool {
                return isset($context['newer_active_automation_id']);
            }));

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);
        $oldFactionLoyaltyAutomation = $this->createFactionLoyaltyAutomation([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
            'completed_at' => null,
        ]);
        $factory->createAutomation();

        (new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1))
            ->failed(new Exception('Job failed.'));

        $this->assertNotNull($oldCharacterAutomation->fresh());
        $this->assertNull($oldFactionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_bails_when_character_is_missing(): void
    {
        Event::fake();

        AutomatedFactionLoyalty::dispatch(999999, 1, 1, 3);

        Event::assertNotDispatched(AutomationLogUpdate::class);
        Event::assertNotDispatched(AutomationTimeOut::class);
        Event::assertNotDispatched(UpdateCharacterStatus::class);
        $this->assertSame(0, CharacterAutomation::count());
        $this->assertSame(0, FactionLoyaltyAutomation::count());
    }

    public function test_handle_ends_automation_when_npc_cannot_be_resolved(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturnNull();
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertNotNull($factionLoyaltyAutomation->fresh()->completed_at);
    }

    public function test_handle_ends_automation_skips_cleanup_when_a_newer_active_automation_appears_mid_run(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturnUsing(function () use ($character, $npc) {
            $newerCharacterAutomation = $this->createCharacterAutomation([
                'character_id' => $character->id,
                'type' => AutomationType::FACTION_LOYALTY->value,
                'started_at' => now(),
                'completed_at' => now()->addHour(),
                'attack_type' => AttackType::ATTACK->value,
            ]);

            $this->createFactionLoyaltyAutomation([
                'character_automation_id' => $newerCharacterAutomation->id,
                'character_id' => $character->id,
                'faction_loyalty_npc_id' => $npc->id,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            return null;
        });
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
        $this->assertNull($factionLoyaltyAutomation->fresh()->completed_at);
    }

    public function test_handle_ends_automation_when_coordinator_signals_should_end_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnTrue();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_ends_automation_when_action_cannot_be_resolved(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturnNull();
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_ends_automation_for_unknown_action_type(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn(['type' => 'unknown', 'task' => []]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_ends_automation_when_crafting_task_missing_item_id(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => [],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_ends_automation_when_fight_task_missing_monster_id(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => [],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_continues_automation_and_reaches_action_completed_phase_when_target_item_is_crafted(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $result = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $item->id)
            ->setCraftedTargetItem(true);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturn($result);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_continues_automation_and_sets_failed_crafting_item_when_started_below_target_level(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $result = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TRAINING_ITEM, $item->id)
            ->setStartedBelowTargetLevel(true);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturn($result);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
        $this->assertSame($item->id, $factionLoyaltyAutomation->fresh()->failed_crafting_item_id);
    }

    public function test_handle_continues_automation_when_crafting_max_attempts_reached(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $result = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturn($result);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_switches_to_bounty_fight_when_not_enough_gold_and_matching_bounty_available(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];
        $monster = $factory->getBountyMonstersForNpc($npc)[0];
        $factionLoyaltyAutomation->update(['failed_bounty_monster_id' => $monster->id]);

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $craftingResult = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturn($craftingResult);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_FIGHT_YIELDED)
            ->setMonsterId($monster->id)
            ->setBountyKills(1);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
        $this->assertNull($factionLoyaltyAutomation->fresh()->failed_bounty_monster_id);
    }

    public function test_handle_switches_to_bounty_fight_using_fallback_task_when_no_bounty_matches_failed_monster(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];
        $monster = $factory->getBountyMonstersForNpc($npc)[0];
        $factionLoyaltyAutomation->update(['failed_bounty_monster_id' => 999999]);

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $craftingResult = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturn($craftingResult);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_FIGHT_YIELDED)
            ->setMonsterId($monster->id);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_ends_automation_when_not_enough_gold_and_no_bounty_available(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setRequiredAmount(0)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $craftingResult = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturn($craftingResult);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame(1, FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $factionLoyaltyAutomation->id)->where('type', AutomatedCraftingResultType::NOT_ENOUGH_GOLD->value)->count());
    }

    public function test_handle_ends_automation_for_unrecognized_crafting_result_type(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $craftingResult = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::ITEM_NOT_FOUND, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturn($craftingResult);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_continues_automation_when_bounty_completed(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $monster = $factory->getBountyMonstersForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => ['monster_id' => $monster->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $fightResult = (new AutomatedFightResult)->setUp(AutomatedFightResultType::BOUNTY_COMPLETED)->setMonsterId($monster->id);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_continues_automation_when_training_batch_completed(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $monster = $factory->getBountyMonstersForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => ['monster_id' => $monster->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $fightResult = (new AutomatedFightResult)->setUp(AutomatedFightResultType::TRAINING_BATCH_COMPLETED)->setMonsterId($monster->id);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_continues_automation_when_died_to_bounty_started_training(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $monster = $factory->getBountyMonstersForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => ['monster_id' => $monster->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $fightResult = (new AutomatedFightResult)->setUp(AutomatedFightResultType::DIED_TO_BOUNTY_STARTED_TRAINING)->setMonsterId($monster->id);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_continues_automation_when_bounty_stalled_retry(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $monster = $factory->getBountyMonstersForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => ['monster_id' => $monster->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $fightResult = (new AutomatedFightResult)->setUp(AutomatedFightResultType::BOUNTY_STALLED_RETRY)->setMonsterId($monster->id);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_ends_automation_with_warning_when_no_training_monster_found(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $monster = $factory->getBountyMonstersForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => ['monster_id' => $monster->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $fightResult = (new AutomatedFightResult)->setUp(AutomatedFightResultType::NO_TRAINING_MONSTER_FOUND)->setMonsterId($monster->id);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame(1, FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $factionLoyaltyAutomation->id)->where('type', AutomatedFightResultType::NO_TRAINING_MONSTER_FOUND->value)->count());
    }

    public function test_handle_ends_automation_with_warning_when_died_during_training(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $monster = $factory->getBountyMonstersForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => ['monster_id' => $monster->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $fightResult = (new AutomatedFightResult)->setUp(AutomatedFightResultType::DIED_DURING_TRAINING)->setMonsterId($monster->id);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame(1, FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $factionLoyaltyAutomation->id)->where('type', AutomatedFightResultType::DIED_DURING_TRAINING->value)->count());
    }

    public function test_handle_ends_automation_with_warning_when_died_to_bounty_after_training(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $monster = $factory->getBountyMonstersForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => ['monster_id' => $monster->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING)
            ->setMonsterId($monster->id)
            ->setBountyKills(0);

        $factionLoyaltyAutomation->update(['failed_bounty_monster_id' => $monster->id]);

        $bountyFightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $bountyFightHandler->shouldReceive('setUp')->andReturnSelf();
        $bountyFightHandler->shouldReceive('handle')->andReturn($fightResult);
        $this->instance(AutomatedBountyFightHandler::class, $bountyFightHandler);

        $fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
        $fightLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationFightLogger::class, $fightLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame(1, FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $factionLoyaltyAutomation->id)->where('type', AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING->value)->count());
        $this->assertSame($monster->id, $factionLoyaltyAutomation->fresh()->failed_bounty_monster_id);
    }

    public function test_handle_recall_job_returns_when_character_automation_deleted_externally(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $result = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturnUsing(function () use ($automation, $result) {
            $automation->delete();

            return $result;
        });
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertNull($factionLoyaltyAutomation->fresh()->completed_at);
    }

    public function test_handle_recall_job_returns_when_faction_loyalty_automation_already_completed(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $result = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturnUsing(function () use ($factionLoyaltyAutomation, $result) {
            $factionLoyaltyAutomation->update(['completed_at' => now()]);

            return $result;
        });
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_recall_job_skips_when_newer_active_automation_appears_mid_run(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $result = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturnUsing(function () use ($character, $npc, $result) {
            $newerCharacterAutomation = $this->createCharacterAutomation([
                'character_id' => $character->id,
                'type' => AutomationType::FACTION_LOYALTY->value,
                'started_at' => now(),
                'completed_at' => now()->addHour(),
                'attack_type' => AttackType::ATTACK->value,
            ]);

            $this->createFactionLoyaltyAutomation([
                'character_automation_id' => $newerCharacterAutomation->id,
                'character_id' => $character->id,
                'faction_loyalty_npc_id' => $npc->id,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            return $result;
        });
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
        $this->assertNull($factionLoyaltyAutomation->fresh()->completed_at);
    }

    public function test_handle_recall_job_ends_automation_when_time_is_up_during_recall(): void
    {
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');
        Carbon::setTestNow($now);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $automation->update(['completed_at' => $now->copy()->addMinute()]);
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();
        $item = $factory->getCraftingItemsForNpc($npc)[0];

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => ['item_id' => $item->id],
        ]);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $result = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $item->id);

        $craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $craftingHandler->shouldReceive('setUp')->andReturnSelf();
        $craftingHandler->shouldReceive('setCraftForNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('setFactionLoyaltyNpc')->andReturnSelf();
        $craftingHandler->shouldReceive('handle')->andReturnUsing(function () use ($now, $result) {
            Carbon::setTestNow($now->copy()->addMinutes(2));

            return $result;
        });
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $craftingLogger->shouldReceive('setUp')->andReturnSelf();
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        Carbon::setTestNow();

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertNotNull($factionLoyaltyAutomation->fresh()->completed_at);
    }

    public function test_handle_handles_missing_inventory_exception(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andThrow(new MissingInventoryException('No inventory found.'));
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertTrue($character->refresh()->user->will_be_deleted);
        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_handles_unexpected_exception_and_ends_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andThrow(new Exception('Something broke.'));
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame(1, FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $factionLoyaltyAutomation->id)->where('type', 'unexpected_exception')->count());
    }

    public function test_handle_broadcasts_log_update_when_character_is_logged_in(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $session = new Session;
        $session->timestamps = false;
        $session->forceFill([
            'id' => Str::random(40),
            'user_id' => $character->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode('a:0:{}'),
            'last_activity' => now()->timestamp,
        ])->save();

        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $automation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $npc = $factory->getAssistingFactionLoyaltyNpc();

        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldReceive('setUp')->andReturnSelf();
        $npcTaskCoordinator->shouldReceive('resolveNpc')->andReturn($npc);
        $npcTaskCoordinator->shouldReceive('shouldEndAutomation')->andReturnFalse();
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);

        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldReceive('setUp')->andReturnSelf();
        $actionCoordinator->shouldReceive('resolveAction')->andReturnNull();
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        AutomatedFactionLoyalty::dispatch($character->id, $automation->id, $factionLoyaltyAutomation->id, 3);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function test_failed_does_not_reactivate_character_when_automation_already_expired(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character, 1, 1)->createAutomation();
        $characterAutomation = $factory->getCharacterAutomation();
        $characterAutomation->update(['completed_at' => now()->subMinute()]);
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $character->update(['can_craft' => false]);

        $job = new AutomatedFactionLoyalty($character->id, $characterAutomation->id, $factionLoyaltyAutomation->id, 1);

        $job->failed(new Exception('Job failed.'));

        $this->assertNull(CharacterAutomation::find($characterAutomation->id));
        $this->assertNotNull($factionLoyaltyAutomation->fresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
    }
}
