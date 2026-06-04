<?php

namespace Tests\Unit\Game\Automation\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Values\AttackTypeValue;
use App\Game\Automation\Coordinators\FactionLoyaltyAutomationActionCoordinator;
use App\Game\Automation\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\Enums\AutomatedCraftingResultType;
use App\Game\Automation\Enums\AutomatedFightResultType;
use App\Game\Automation\Enums\FactionLoyaltyCoordinatorAction;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Handlers\AutomatedBountyFightHandler;
use App\Game\Automation\Handlers\AutomatedCraftingHandler;
use App\Game\Automation\Jobs\AutomatedFactionLoyalty;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationFightLogger;
use App\Game\Automation\Values\AutomatedCraftingResult;
use App\Game\Automation\Values\AutomatedFightResult;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyAutomationWarningState;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class AutomatedFactionLoyaltyTest extends TestCase
{
    use RefreshDatabase;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?Character $character = null;

    private ?CharacterAutomation $characterAutomation = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?CharacterCacheData $characterCacheData = null;

    private MockInterface|FactionLoyaltyNpcTaskCoordinator|null $npcTaskCoordinator = null;

    private MockInterface|FactionLoyaltyAutomationActionCoordinator|null $actionCoordinator = null;

    private MockInterface|AutomatedCraftingHandler|null $craftingHandler = null;

    private MockInterface|FactionLoyaltyAutomationCraftingLogger|null $craftingLogger = null;

    private MockInterface|AutomatedBountyFightHandler|null $fightHandler = null;

    private MockInterface|FactionLoyaltyAutomationFightLogger|null $fightLogger = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->createSessionForCharacter()
            ->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->characterAutomation = $this->factionLoyaltyFactory->getCharacterAutomation();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();

        $this->characterCacheData = Mockery::mock(CharacterCacheData::class);
        $this->characterCacheData->shouldReceive('deleteCharacterSheet')->zeroOrMoreTimes();

        $this->npcTaskCoordinator = Mockery::mock(FactionLoyaltyNpcTaskCoordinator::class);
        $this->actionCoordinator = Mockery::mock(FactionLoyaltyAutomationActionCoordinator::class);
        $this->craftingHandler = Mockery::mock(AutomatedCraftingHandler::class);
        $this->craftingLogger = Mockery::mock(FactionLoyaltyAutomationCraftingLogger::class);
        $this->fightHandler = Mockery::mock(AutomatedBountyFightHandler::class);
        $this->fightLogger = Mockery::mock(FactionLoyaltyAutomationFightLogger::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        $this->factionLoyaltyFactory = null;
        $this->character = null;
        $this->characterAutomation = null;
        $this->factionLoyaltyAutomation = null;
        $this->factionLoyaltyNpc = null;
        $this->characterCacheData = null;
        $this->npcTaskCoordinator = null;
        $this->actionCoordinator = null;
        $this->craftingHandler = null;
        $this->craftingLogger = null;
        $this->fightHandler = null;
        $this->fightLogger = null;

        Mockery::close();

        parent::tearDown();
    }

    public function test_handle_bails_when_character_cannot_be_found(): void
    {
        Event::fake();

        $job = new AutomatedFactionLoyalty(
            999999,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_bails_when_character_automation_cannot_be_found(): void
    {
        Event::fake();

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            999999,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_bails_when_faction_loyalty_automation_cannot_be_found(): void
    {
        Event::fake();

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            999999,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
    }

    public function test_handle_bails_when_faction_loyalty_automation_is_already_completed(): void
    {
        Event::fake();

        $this->factionLoyaltyAutomation->update([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
    }

    public function test_handle_bails_when_character_automation_is_expired(): void
    {
        Event::fake();

        $this->characterAutomation->update([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
    }

    public function test_handle_ends_automation_when_no_npc_can_be_resolved(): void
    {
        Event::fake();

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturnNull();

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_ends_automation_when_npc_task_coordinator_says_automation_should_end(): void
    {
        Event::fake();

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnTrue();

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_ends_automation_when_no_automation_action_can_be_resolved(): void
    {
        Event::fake();

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturnNull();

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_ends_automation_when_resolved_action_type_is_unknown(): void
    {
        Event::fake();

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => 'unknown',
            'task' => [],
        ]);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_ends_automation_when_craft_action_is_missing_item_id(): void
    {
        Event::fake();

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => [
                'type' => 'weapon',
            ],
        ]);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_handles_crafted_target_item_and_recalls_the_job(): void
    {
        Queue::fake();
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $craftingTask['item_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturnUsing(function () use ($craftingResult): AutomatedCraftingResult {
            resolve(FactionLoyaltyAutomationCraftingLogger::class)
                ->setUp($this->factionLoyaltyAutomation)
                ->log($craftingResult);

            return $craftingResult;
        });

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_handles_crafted_training_item_and_recalls_the_job(): void
    {
        Queue::fake();
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TRAINING_ITEM, $craftingTask['item_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturnUsing(function () use ($craftingResult): AutomatedCraftingResult {
            resolve(FactionLoyaltyAutomationCraftingLogger::class)
                ->setUp($this->factionLoyaltyAutomation)
                ->log($craftingResult);

            return $craftingResult;
        });

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_handles_max_attempts_reached_and_recalls_the_job(): void
    {
        Queue::fake();
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED, $craftingTask['item_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturnUsing(function () use ($craftingResult): AutomatedCraftingResult {
            resolve(FactionLoyaltyAutomationCraftingLogger::class)
                ->setUp($this->factionLoyaltyAutomation)
                ->log($craftingResult);

            return $craftingResult;
        });

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_sets_failed_crafting_item_when_crafting_started_below_target_level(): void
    {
        Queue::fake();
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TRAINING_ITEM, $craftingTask['item_id'])
            ->setStartedBelowTargetLevel(true);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturn($craftingResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertEquals($craftingTask['item_id'], $this->factionLoyaltyAutomation->refresh()->failed_crafting_item_id);
    }

    public function test_handle_clears_failed_crafting_item_when_target_item_is_crafted(): void
    {
        Queue::fake();
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $this->factionLoyaltyAutomation->update([
            'failed_crafting_item_id' => $craftingTask['item_id'],
        ]);
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $craftingTask['item_id'])
            ->setCraftedTargetItem(true);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturn($craftingResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->factionLoyaltyAutomation->refresh()->failed_crafting_item_id);
    }

    public function test_handle_switches_from_crafting_to_bounty_fighting_when_crafting_cannot_continue_because_of_not_enough_gold(): void
    {
        Queue::fake();
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $craftingTask['item_id']);
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_COMPLETED)
            ->setMonsterId($bountyTask['monster_id'])
            ->setBountyKills(1);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturn($craftingResult);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->with(
            Mockery::type(Character::class),
            Mockery::type(FactionLoyaltyAutomation::class),
            Mockery::type(FactionLoyaltyNpc::class),
            Mockery::on(fn (array $task): bool => $task['monster_id'] === $bountyTask['monster_id']),
            AttackTypeValue::ATTACK,
            $this->fightLogger
        )->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_ends_automation_when_crafting_cannot_continue_because_of_not_enough_gold_and_no_bounty_task_exists(): void
    {
        Event::fake();

        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;
        $craftingTask = collect($fameTasks)->first(fn (array $task): bool => isset($task['item_id']));

        foreach ($fameTasks as $index => $fameTask) {
            if (($fameTask['type'] ?? null) === 'bounty') {
                $fameTasks[$index]['current_amount'] = $fameTask['required_amount'];
            }
        }

        $this->factionLoyaltyNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $fameTasks,
        ]);

        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $craftingTask['item_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc->refresh());
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturnUsing(function () use ($craftingResult): AutomatedCraftingResult {
            resolve(FactionLoyaltyAutomationCraftingLogger::class)
                ->setUp($this->factionLoyaltyAutomation)
                ->log($craftingResult);

            return $craftingResult;
        });

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event): bool {
            return $event->message === 'Not enough gold to craft and no bounty remains for this NPC. Automation has ended.';
        });
        $warning = FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $this->factionLoyaltyAutomation->id)->first();

        $this->assertNotNull($warning);
        $this->assertEquals($this->character->id, $warning->character_id);
        $this->assertEquals($this->factionLoyaltyNpc->id, $warning->faction_loyalty_npc_id);
        $this->assertEquals($this->factionLoyaltyAutomation->log->id, $warning->faction_loyalty_automation_log_id);
        $this->assertEquals('crafting_logs', $warning->log_type);
        $this->assertNotNull($warning->log_entry_id);
        $this->assertEquals($this->factionLoyaltyAutomation->refresh()->log->crafting_logs[0]['log_entry_id'], $warning->log_entry_id);
        $this->assertEquals(AutomatedCraftingResultType::NOT_ENOUGH_GOLD->value, $warning->type);
        $this->assertEquals('Not enough gold to craft and no bounty remains for this NPC. Automation has ended.', $warning->message);
        Event::assertDispatched(FactionLoyaltyAutomationWarningState::class, function (FactionLoyaltyAutomationWarningState $event) use ($warning): bool {
            return $event->has_warning &&
                $event->warning_notices === [
                    [
                        'id' => $warning->id,
                        'type' => AutomatedCraftingResultType::NOT_ENOUGH_GOLD->value,
                        'message' => 'Not enough gold to craft and no bounty remains for this NPC. Automation has ended.',
                    ],
                ] &&
                $event->warning_notice === [
                    'id' => $warning->id,
                    'type' => AutomatedCraftingResultType::NOT_ENOUGH_GOLD->value,
                    'message' => 'Not enough gold to craft and no bounty remains for this NPC. Automation has ended.',
                ];
        });
    }

    public function test_handle_ends_automation_when_crafting_result_cannot_continue(): void
    {
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::ITEM_NOT_FOUND, $craftingTask['item_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturn($craftingResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_uses_matching_failed_bounty_task_when_crafting_cannot_continue_because_of_not_enough_gold(): void
    {
        Queue::fake();
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $bountyTasks = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->filter(fn (array $task): bool => ($task['type'] ?? null) === 'bounty')
            ->values();
        $matchingBountyTask = $bountyTasks[1];
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $matchingBountyTask['monster_id'],
        ]);
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $craftingTask['item_id']);
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_COMPLETED)
            ->setMonsterId($matchingBountyTask['monster_id'])
            ->setBountyKills(1);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturn($craftingResult);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->with(
            Mockery::type(Character::class),
            Mockery::type(FactionLoyaltyAutomation::class),
            Mockery::type(FactionLoyaltyNpc::class),
            Mockery::on(fn (array $task): bool => $task['monster_id'] === $matchingBountyTask['monster_id']),
            AttackTypeValue::ATTACK,
            $this->fightLogger
        )->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_falls_back_to_any_bounty_task_when_failed_bounty_task_is_not_available_after_not_enough_gold(): void
    {
        Queue::fake();
        Event::fake();

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => 999999,
        ]);
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $craftingTask['item_id']);
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_COMPLETED)
            ->setMonsterId($bountyTask['monster_id'])
            ->setBountyKills(1);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturn($craftingResult);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->with(
            Mockery::type(Character::class),
            Mockery::type(FactionLoyaltyAutomation::class),
            Mockery::type(FactionLoyaltyNpc::class),
            Mockery::on(fn (array $task): bool => $task['monster_id'] === $bountyTask['monster_id']),
            AttackTypeValue::ATTACK,
            $this->fightLogger
        )->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_ends_automation_when_fight_action_is_missing_monster_id(): void
    {
        Event::fake();

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => [
                'type' => 'bounty',
            ],
        ]);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_handles_bounty_completed_and_recalls_the_job(): void
    {
        Queue::fake();
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_COMPLETED)
            ->setMonsterId($bountyTask['monster_id'])
            ->setBountyKills(1);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_handles_training_batch_completed_and_recalls_the_job(): void
    {
        Queue::fake();
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::TRAINING_BATCH_COMPLETED)
            ->setMonsterId($bountyTask['monster_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_handles_died_to_bounty_started_training_and_recalls_the_job(): void
    {
        Queue::fake();
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::DIED_TO_BOUNTY_STARTED_TRAINING)
            ->setMonsterId($bountyTask['monster_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
        $this->assertNull(FactionLoyaltyAutomationWarning::query()->first());
    }

    public function test_handle_creates_warning_when_no_training_monster_found_ends_automation(): void
    {
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::NO_TRAINING_MONSTER_FOUND)
            ->setMonsterId($bountyTask['monster_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturnUsing(function () use ($fightResult): AutomatedFightResult {
            resolve(FactionLoyaltyAutomationFightLogger::class)
                ->setUp($this->factionLoyaltyAutomation)
                ->log($fightResult);

            return $fightResult;
        });

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $warning = FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $this->factionLoyaltyAutomation->id)->first();

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($warning);
        $this->assertEquals($this->character->id, $warning->character_id);
        $this->assertEquals($this->factionLoyaltyNpc->id, $warning->faction_loyalty_npc_id);
        $this->assertEquals($this->factionLoyaltyAutomation->log->id, $warning->faction_loyalty_automation_log_id);
        $this->assertEquals('fight_logs', $warning->log_type);
        $this->assertNotNull($warning->log_entry_id);
        $this->assertEquals($this->factionLoyaltyAutomation->refresh()->log->fight_logs[0]['log_entry_id'], $warning->log_entry_id);
        $this->assertEquals(AutomatedFightResultType::NO_TRAINING_MONSTER_FOUND->value, $warning->type);
        $this->assertEquals('No recovery monster found. Automation has ended.', $warning->message);
    }

    public function test_handle_creates_warning_when_died_during_training_ends_automation(): void
    {
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::DIED_DURING_TRAINING)
            ->setMonsterId($bountyTask['monster_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturnUsing(function () use ($fightResult): AutomatedFightResult {
            resolve(FactionLoyaltyAutomationFightLogger::class)
                ->setUp($this->factionLoyaltyAutomation)
                ->log($fightResult);

            return $fightResult;
        });

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $warning = FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $this->factionLoyaltyAutomation->id)->first();

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($warning);
        $this->assertEquals($this->character->id, $warning->character_id);
        $this->assertEquals($this->factionLoyaltyNpc->id, $warning->faction_loyalty_npc_id);
        $this->assertEquals($this->factionLoyaltyAutomation->log->id, $warning->faction_loyalty_automation_log_id);
        $this->assertEquals('fight_logs', $warning->log_type);
        $this->assertNotNull($warning->log_entry_id);
        $this->assertEquals($this->factionLoyaltyAutomation->refresh()->log->fight_logs[0]['log_entry_id'], $warning->log_entry_id);
        $this->assertEquals(AutomatedFightResultType::DIED_DURING_TRAINING->value, $warning->type);
        $this->assertEquals('You died during recovery training. Automation has ended.', $warning->message);
    }

    public function test_handle_creates_warning_when_died_to_bounty_after_training_ends_automation(): void
    {
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING)
            ->setMonsterId($bountyTask['monster_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturnUsing(function () use ($fightResult): AutomatedFightResult {
            resolve(FactionLoyaltyAutomationFightLogger::class)
                ->setUp($this->factionLoyaltyAutomation)
                ->log($fightResult);

            return $fightResult;
        });

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $warning = FactionLoyaltyAutomationWarning::where('faction_loyalty_automation_id', $this->factionLoyaltyAutomation->id)->first();

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($warning);
        $this->assertEquals($this->character->id, $warning->character_id);
        $this->assertEquals($this->factionLoyaltyNpc->id, $warning->faction_loyalty_npc_id);
        $this->assertEquals($this->factionLoyaltyAutomation->log->id, $warning->faction_loyalty_automation_log_id);
        $this->assertEquals('fight_logs', $warning->log_type);
        $this->assertNotNull($warning->log_entry_id);
        $this->assertEquals($this->factionLoyaltyAutomation->refresh()->log->fight_logs[0]['log_entry_id'], $warning->log_entry_id);
        $this->assertEquals(AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING->value, $warning->type);
        $this->assertEquals('You died fighting the bounty after recovery training. Automation has ended.', $warning->message);
    }

    public function test_handle_handles_bounty_stalled_retry_and_recalls_the_job(): void
    {
        Queue::fake();
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_STALLED_RETRY)
            ->setMonsterId($bountyTask['monster_id'])
            ->setStalledAttempt(1);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertPushed(AutomatedFactionLoyalty::class);
    }

    public function test_handle_clears_failed_bounty_monster_when_failed_bounty_monster_is_killed(): void
    {
        Queue::fake();
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $bountyTask['monster_id'],
        ]);
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_COMPLETED)
            ->setMonsterId($bountyTask['monster_id'])
            ->setBountyKills(1);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->factionLoyaltyAutomation->refresh()->failed_bounty_monster_id);
    }

    public function test_handle_does_not_clear_failed_bounty_monster_when_fight_result_does_not_match_failed_monster(): void
    {
        Queue::fake();
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $bountyTask['monster_id'],
        ]);
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_COMPLETED)
            ->setMonsterId($bountyTask['monster_id'] + 999)
            ->setBountyKills(1);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertEquals($bountyTask['monster_id'], $this->factionLoyaltyAutomation->refresh()->failed_bounty_monster_id);
    }

    public function test_handle_does_not_clear_failed_bounty_monster_when_no_bounty_kills_were_made(): void
    {
        Queue::fake();
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $bountyTask['monster_id'],
        ]);
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_COMPLETED)
            ->setMonsterId($bountyTask['monster_id'])
            ->setBountyKills(0);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertEquals($bountyTask['monster_id'], $this->factionLoyaltyAutomation->refresh()->failed_bounty_monster_id);
    }

    public function test_handle_ends_automation_for_non_recall_fight_results(): void
    {
        Event::fake();

        $bountyTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => ($task['type'] ?? null) === 'bounty');
        $fightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::ERROR)
            ->setMonsterId($bountyTask['monster_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $bountyTask,
        ]);
        $this->fightLogger->shouldReceive('setUp')->once()->andReturn($this->fightLogger);
        $this->fightHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->fightHandler->shouldReceive('handle')->once()->andReturn($fightResult);

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }

    public function test_handle_ends_automation_instead_of_recalling_when_automation_expires_after_crafting_result(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');
        Carbon::setTestNow($now);

        $this->characterAutomation->update([
            'completed_at' => $now->copy()->addSecond(),
        ]);

        $craftingTask = collect($this->factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->first(fn (array $task): bool => isset($task['item_id']));
        $craftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, $craftingTask['item_id']);

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->npcTaskCoordinator->shouldReceive('resolveNpc')->once()->andReturn($this->factionLoyaltyNpc);
        $this->npcTaskCoordinator->shouldReceive('shouldEndAutomation')->once()->andReturnFalse();
        $this->actionCoordinator->shouldReceive('setUp')->once()->andReturnSelf();
        $this->actionCoordinator->shouldReceive('resolveAction')->once()->andReturn([
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $craftingTask,
        ]);
        $this->craftingLogger->shouldReceive('setUp')->once()->andReturn($this->craftingLogger);
        $this->craftingHandler->shouldReceive('setUp')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setCraftForNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('setFactionLoyaltyNpc')->once()->andReturnSelf();
        $this->craftingHandler->shouldReceive('handle')->once()->andReturnUsing(function () use ($craftingResult, $now): AutomatedCraftingResult {
            Carbon::setTestNow($now->copy()->addSecond());

            return $craftingResult;
        });

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        Queue::assertNotPushed(AutomatedFactionLoyalty::class);
        $this->assertNull($this->characterAutomation->fresh());
    }

    public function test_handle_logs_and_ends_automation_on_exception(): void
    {
        Event::fake();

        Log::shouldReceive('error')->once();

        $this->npcTaskCoordinator->shouldReceive('setUp')->once()->andThrow(new Exception('Failed to resolve npc.'));

        $job = new AutomatedFactionLoyalty(
            $this->character->id,
            $this->characterAutomation->id,
            $this->factionLoyaltyAutomation->id,
            1
        );

        $job->handle(
            $this->characterCacheData,
            $this->npcTaskCoordinator,
            $this->actionCoordinator,
            $this->craftingHandler,
            $this->craftingLogger,
            $this->fightHandler,
            $this->fightLogger
        );

        $this->assertNull($this->characterAutomation->fresh());
        $this->assertNotNull($this->factionLoyaltyAutomation->refresh()->completed_at);
    }
}
