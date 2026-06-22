<?php

namespace Tests\Unit\Game\Automation\Jobs;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Game\Automation\Coordinators\FactionLoyaltyAutomationActionCoordinator;
use App\Game\Automation\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\Enums\AutomatedCraftingResultType;
use App\Game\Automation\Enums\FactionLoyaltyCoordinatorAction;
use App\Game\Automation\Handlers\AutomatedCraftingHandler;
use App\Game\Automation\Jobs\AutomatedFactionLoyalty;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\Values\AutomatedCraftingResult;
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

    public function testMissingExactCharacterAutomationDoesNotDeleteNewerActiveFactionLoyaltyAutomation(): void
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

    public function testMissingExactFactionLoyaltyAutomationDoesNotDeleteNewerActiveFactionLoyaltyAutomation(): void
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

    public function testCompletedExactFactionLoyaltyAutomationDoesNotDeleteNewerActiveFactionLoyaltyAutomation(): void
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

    public function testExpiredExactCharacterAutomationEndsOnlyExactAutomation(): void
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

    public function testStaleJobCannotDeleteNewerActiveFactionLoyaltyAutomation(): void
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

    public function testStaleJobReturnsBeforeResolvingNpcOrAction(): void
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

    public function testStaleJobDoesNotDeleteOldAutomation(): void
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

    public function testStaleJobDoesNotCompleteOldFactionLoyaltyAutomation(): void
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

    public function testStaleJobDoesNotAlterNewerActiveAutomation(): void
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

    public function testStaleJobDoesNotEnableCrafting(): void
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

    public function testStaleJobDoesNotRecallItself(): void
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

    public function testStoppedAutomationDoesNotRecallItself(): void
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

    public function testRecallJobDispatchesToFactionLoyaltyQueueOnLongRunningConnectionForValidRecords(): void
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

    public function testRecallJobDoesNotDispatchWhenNewerActiveFactionLoyaltyAutomationExists(): void
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

    public function testExceptionHandlingDoesNotAlterAutomationStateWhenNewerActiveAutomationExists(): void
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

    public function testRecallJobDoesNotDispatchWhenExactFactionLoyaltyAutomationIsCompleted(): void
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

    public function testFailedLogsAndCleansUpOnlyExactOwnedAutomation(): void
    {
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
    }

    public function testFailedDoesNotAlterNewerActiveAutomation(): void
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

    public function testFailedStaleJobLogsAndReturnsWithoutCleanup(): void
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

    public function testFailedDoesNotDeleteOldAutomationWhenNewerActiveAutomationExists(): void
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

    public function testFailedDoesNotCompleteOldFactionLoyaltyAutomationWhenNewerActiveAutomationExists(): void
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

    public function testFailedDoesNotEnableCraftingWhenNewerActiveAutomationExists(): void
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

    public function testJobTriesOnce(): void
    {
        $job = new AutomatedFactionLoyalty(1, 2, 3, 1);

        $this->assertEquals(1, $job->tries);
    }

    public function testJobTimesOutAfterOneHundredTwentySeconds(): void
    {
        $job = new AutomatedFactionLoyalty(1, 2, 3, 1);

        $this->assertEquals(120, $job->timeout);
    }

    public function testJobFailsOnTimeout(): void
    {
        $job = new AutomatedFactionLoyalty(1, 2, 3, 1);

        $this->assertTrue($job->failOnTimeout);
    }
}
