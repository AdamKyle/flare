<?php

namespace Tests\Unit\Game\Automation\Jobs;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Values\AttackTypeValue;
use App\Game\Automation\Coordinators\FactionLoyaltyAutomationActionCoordinator;
use App\Game\Automation\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\Enums\AutomatedCraftingResultType;
use App\Game\Automation\Enums\FactionLoyaltyCoordinatorAction;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Handlers\AutomatedCraftingHandler;
use App\Game\Automation\Jobs\AutomatedFactionLoyalty;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\Values\AutomatedCraftingResult;
use App\Game\Battle\Events\UpdateCharacterStatus;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class AutomatedFactionLoyaltyTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_exact_character_automation_does_not_delete_newer_active_faction_loyalty_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $newerCharacterAutomation = $factory->getCharacterAutomation();
        $newerFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $character->update(['can_craft' => false]);
        $job = new AutomatedFactionLoyalty($character->id, $newerCharacterAutomation->id + 1000, $newerFactionLoyaltyAutomation->id + 1000, 1);

        $this->app->call([$job, 'handle']);

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
        $staleCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $character->update(['can_craft' => false]);
        $job = new AutomatedFactionLoyalty($character->id, $staleCharacterAutomation->id, $newerFactionLoyaltyAutomation->id + 1000, 1);

        $this->app->call([$job, 'handle']);

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
        $staleCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $completedFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $staleCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
        $character->update(['can_craft' => false]);
        $job = new AutomatedFactionLoyalty($character->id, $staleCharacterAutomation->id, $completedFactionLoyaltyAutomation->id, 1);

        $this->app->call([$job, 'handle']);

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
        $unrelatedAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $job = new AutomatedFactionLoyalty($character->id, $exactCharacterAutomation->id, $exactFactionLoyaltyAutomation->id, 1);

        $this->app->call([$job, 'handle']);

        $this->assertNull($exactCharacterAutomation->fresh());
        $this->assertNotNull($exactFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertNotNull($unrelatedAutomation->fresh());
    }

    public function test_stale_job_cannot_delete_newer_active_faction_loyalty_automation(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $newerCharacterAutomation = $factory->getCharacterAutomation();
        $newerFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $character->update(['can_craft' => false]);
        $job = new AutomatedFactionLoyalty($character->id, $newerCharacterAutomation->id + 1000, $newerFactionLoyaltyAutomation->id + 1000, 1);

        $this->app->call([$job, 'handle']);

        $this->assertNotNull($newerCharacterAutomation->fresh());
        $this->assertNull($newerFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
        Queue::assertNothingPushed();
    }

    public function test_stale_job_returns_before_resolving_npc_or_action(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();
        $npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $npcTaskCoordinator->shouldNotReceive('setUp');
        $actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $actionCoordinator->shouldNotReceive('setUp');
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);

        $this->app->call([(new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1)), 'handle']);
    }

    public function test_stale_job_does_not_delete_old_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();

        $this->app->call([(new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1)), 'handle']);

        $this->assertNotNull($oldCharacterAutomation->fresh());
    }

    public function test_stale_job_does_not_complete_old_faction_loyalty_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();

        $this->app->call([(new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1)), 'handle']);

        $this->assertNull($oldFactionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_stale_job_does_not_alter_newer_active_automation(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();
        $newerCharacterAutomation = $factory->getCharacterAutomation();
        $newerFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();

        $this->app->call([(new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1)), 'handle']);

        $this->assertNotNull($newerCharacterAutomation->fresh());
        $this->assertNull($newerFactionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_stale_job_does_not_enable_crafting(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();
        $character->update(['can_craft' => false]);

        $this->app->call([(new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1)), 'handle']);

        $this->assertFalse($character->refresh()->can_craft);
    }

    public function test_stale_job_does_not_recall_itself(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();

        $this->app->call([(new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1)), 'handle']);

        Queue::assertNothingPushed();
    }

    public function test_stopped_automation_does_not_recall_itself(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $stoppedCharacterAutomation = $factory->getCharacterAutomation();
        $stoppedFactionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $stoppedCharacterAutomation->delete();
        $stoppedFactionLoyaltyAutomation->update(['completed_at' => now()]);
        $job = new AutomatedFactionLoyalty($character->id, $stoppedCharacterAutomation->id, $stoppedFactionLoyaltyAutomation->id, 1);

        $this->app->call([$job, 'handle']);

        Queue::assertNothingPushed();
    }

    public function test_recall_job_dispatches_to_faction_loyalty_queue_on_long_running_connection_for_valid_records(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $characterAutomation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $factionLoyaltyNpc = $factory->getAssistingFactionLoyaltyNpc();
        $itemId = $factory->getCraftingItemsForNpc($factionLoyaltyNpc)[0]->id;
        $result = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $itemId);

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
        $craftingHandler->shouldReceive('handle')->once()->andReturn($result);

        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $job = new AutomatedFactionLoyalty($character->id, $characterAutomation->id, $factionLoyaltyAutomation->id, 1);

        $this->app->call([$job, 'handle']);

        Queue::assertPushed(AutomatedFactionLoyalty::class, function (AutomatedFactionLoyalty $recalledJob): bool {
            return $recalledJob->connection === 'long_running'
                && $recalledJob->queue === 'faction_loyalty';
        });
    }

    public function test_recall_job_does_not_dispatch_when_newer_active_faction_loyalty_automation_exists(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $characterAutomation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $factionLoyaltyNpc = $factory->getAssistingFactionLoyaltyNpc();
        $itemId = $factory->getCraftingItemsForNpc($factionLoyaltyNpc)[0]->id;
        $result = (new AutomatedCraftingResult)->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $itemId);
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
            ->andReturnUsing(function () use ($factory, $result): AutomatedCraftingResult {
                $factory->createAutomation();

                return $result;
            });
        $this->instance(FactionLoyaltyNpcTaskCoordinator::class, $npcTaskCoordinator);
        $this->instance(FactionLoyaltyAutomationActionCoordinator::class, $actionCoordinator);
        $this->instance(FactionLoyaltyAutomationCraftingLogger::class, $craftingLogger);
        $this->instance(AutomatedCraftingHandler::class, $craftingHandler);

        $this->app->call([(new AutomatedFactionLoyalty($character->id, $characterAutomation->id, $factionLoyaltyAutomation->id, 1)), 'handle']);

        Queue::assertNothingPushed();
    }

    public function test_exception_handling_does_not_alter_automation_state_when_newer_active_automation_exists(): void
    {
        Event::fake();
        Queue::fake();

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

        $this->app->call([(new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1)), 'handle']);

        $this->assertNotNull($oldCharacterAutomation->fresh());
        $this->assertNull($oldFactionLoyaltyAutomation->refresh()->completed_at);
        $this->assertNotNull($factory->getCharacterAutomation()->fresh());
        $this->assertNull($factory->getFactionLoyaltyAutomation()->refresh()->completed_at);
        $this->assertFalse($character->refresh()->can_craft);
        Queue::assertNothingPushed();
    }

    public function test_recall_job_does_not_dispatch_when_exact_faction_loyalty_automation_is_completed(): void
    {
        Event::fake();
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character)->createAutomation();
        $characterAutomation = $factory->getCharacterAutomation();
        $factionLoyaltyAutomation = $factory->getFactionLoyaltyAutomation();
        $factionLoyaltyAutomation->update(['completed_at' => now()]);
        $job = new AutomatedFactionLoyalty($character->id, $characterAutomation->id, $factionLoyaltyAutomation->id, 1);

        $this->app->call([$job, 'handle']);

        Queue::assertNothingPushed();
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
        $staleCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $staleFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $staleCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
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
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();

        (new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1))
            ->failed(new Exception('Job failed.'));
    }

    public function test_failed_does_not_delete_old_automation_when_newer_active_automation_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();

        (new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1))
            ->failed(new Exception('Job failed.'));

        $this->assertNotNull($oldCharacterAutomation->fresh());
    }

    public function test_failed_does_not_complete_old_faction_loyalty_automation_when_newer_active_automation_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();

        (new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1))
            ->failed(new Exception('Job failed.'));

        $this->assertNull($oldFactionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_failed_does_not_enable_crafting_when_newer_active_automation_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factory = (new FactionLoyaltyFactory)->setUp($character);
        $oldCharacterAutomation = CharacterAutomation::create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $oldFactionLoyaltyAutomation = FactionLoyaltyAutomation::create([
            'character_automation_id' => $oldCharacterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factory->getAssistingFactionLoyaltyNpc()->id,
            'started_at' => now()->subMinute(),
        ]);
        $factory->createAutomation();
        $character->update(['can_craft' => false]);

        (new AutomatedFactionLoyalty($character->id, $oldCharacterAutomation->id, $oldFactionLoyaltyAutomation->id, 1))
            ->failed(new Exception('Job failed.'));

        $this->assertFalse($character->refresh()->can_craft);
    }

    public function test_job_tries_once(): void
    {
        $job = new AutomatedFactionLoyalty(1, 2, 3, 1);

        $this->assertEquals(1, $job->tries);
    }

    public function test_job_times_out_after_one_hundred_twenty_seconds(): void
    {
        $job = new AutomatedFactionLoyalty(1, 2, 3, 1);

        $this->assertEquals(120, $job->timeout);
    }

    public function test_job_fails_on_timeout(): void
    {
        $job = new AutomatedFactionLoyalty(1, 2, 3, 1);

        $this->assertTrue($job->failOnTimeout);
    }
}
