<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Automation\Events\DelveStatusUpdated;
use App\Game\Automation\Events\ExplorationOutputUpdated;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\QueueFake;
use League\Fractal\Manager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Exception;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ProcessCharacterBattleRewardQueueTest extends TestCase
{
    use MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_processor_drains_first_priority_before_second_and_marks_queue_inactive(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $second = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::SECOND,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 22, 'context' => []],
        ]);
        $first = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::FIRST,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'handler_payload' => ['quest_id' => 999999],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->with($character->id, 22)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->with([])->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once()->with(true);

        $job = new ProcessCharacterBattleRewardQueue($character->id);
        $job->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::FAILED, $first->refresh()->status);
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $second->refresh()->status);
        $this->assertFalse(CharacterBattleRewardQueueState::where('character_id', $character->id)->firstOrFail()->is_processing);
    }

    public function test_processor_continues_after_failure(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $failed = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);
        $completed = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => 2, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->twice()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->with($character->id, 1)->once()->andThrow(new RuntimeException('reward failed'));
        $battleRewardService->shouldReceive('setUp')->with($character->id, 2)->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->with([])->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->with(true)->once();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::FAILED, $failed->refresh()->status);
        $this->assertStringContainsString('reward failed', $failed->failed_reason);
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $completed->refresh()->status);
    }

    public function test_processor_continuation_has_no_delay_when_backlog_remains_after_max_requests(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $queueFake = new class(app(), [], Queue::getFacadeRoot(), $character->id) extends QueueFake
        {
            public array $lockWasAvailableWhenPushed = [];

            private int $characterId;

            public function __construct($app, array $jobsToFake, $queue, int $characterId)
            {
                parent::__construct($app, $jobsToFake, $queue);

                $this->characterId = $characterId;
            }

            public function push($job, $data = '', $queue = null)
            {
                if ($job instanceof ProcessCharacterBattleRewardQueue) {
                    $lock = Cache::lock('character-reward-queue:'.$this->characterId, 60);

                    if ($lock->get()) {
                        $this->lockWasAvailableWhenPushed[] = true;
                        $lock->release();
                    } else {
                        $this->lockWasAvailableWhenPushed[] = false;
                    }
                }

                return parent::push($job, $data, $queue);
            }
        };
        Queue::swap($queueFake);
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);

        for ($i = 1; $i <= 51; $i++) {
            CharacterBattleRewardRequest::factory()->create([
                'character_id' => $character->id,
                'priority' => BattleRewardRequestPriority::SECOND,
                'source_type' => BattleRewardRequestSourceType::BATTLE,
                'handler_payload' => ['monster_id' => $i, 'context' => []],
            ]);
        }

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->times(50)->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->times(50)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->times(50)->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->times(50);

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, function ($job) {
            return is_null($job->delay);
        });
        $this->assertSame([true], $queueFake->lockWasAvailableWhenPushed);
    }

    public function test_continuation_job_dispatched_after_unlock_can_process_next_pending_request(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $lastRequest = null;

        for ($requestNumber = 1; $requestNumber <= 51; $requestNumber++) {
            $lastRequest = CharacterBattleRewardRequest::factory()->create([
                'character_id' => $character->id,
                'priority' => BattleRewardRequestPriority::SECOND,
                'source_type' => BattleRewardRequestSourceType::BATTLE,
                'handler_payload' => ['monster_id' => $requestNumber, 'context' => []],
            ]);
        }

        $firstBattleRewardService = Mockery::mock(BattleRewardService::class);
        $firstBattleRewardService->shouldReceive('withHeartbeatCallback')->times(50)->andReturnSelf();
        $firstBattleRewardService->shouldReceive('setUp')->times(50)->andReturnSelf();
        $firstBattleRewardService->shouldReceive('setContext')->times(50)->andReturnSelf();
        $firstBattleRewardService->shouldReceive('processRewards')->times(50);

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $firstBattleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::PENDING, $lastRequest->refresh()->status);

        $secondBattleRewardService = Mockery::mock(BattleRewardService::class);
        $secondBattleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $secondBattleRewardService->shouldReceive('setUp')->once()->with($character->id, 51)->andReturnSelf();
        $secondBattleRewardService->shouldReceive('setContext')->once()->with([])->andReturnSelf();
        $secondBattleRewardService->shouldReceive('processRewards')->once();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $secondBattleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $lastRequest->refresh()->status);
    }

    public function test_processor_handles_exploration_source_type_and_fires_player_updates(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
            'handler_payload' => ['monster_id' => 55, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->with($character->id, 55)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->with([])->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once()->with(true);

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
    }

    public function test_processor_handles_automation_source_type_and_fires_player_updates(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::AUTOMATION,
            'handler_payload' => ['monster_id' => 77, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->with($character->id, 77)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->with([])->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once()->with(true);

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
    }

    public function test_processor_drains_request_inserted_while_processing(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->twice()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->with($character->id, 1)->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->with([])->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->with(true)->once()->andReturnUsing(
            function () use ($character): void {
                CharacterBattleRewardRequest::factory()->create([
                    'character_id' => $character->id,
                    'handler_payload' => ['monster_id' => 2, 'context' => []],
                ]);
            },
        );
        $battleRewardService->shouldReceive('setUp')->with($character->id, 2)->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->with([])->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->with(true)->once();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(
            2,
            CharacterBattleRewardRequest::forCharacter($character->id)->completed()->count(),
        );
    }

    public function test_second_processor_for_same_character_exits_safely_when_lock_is_held(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);
        $lock = Cache::lock('character-reward-queue:'.$character->id, 60);
        $lock->get();

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldNotReceive('withHeartbeatCallback');

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $lock->release();

        $this->assertSame(BattleRewardRequestStatus::PENDING, $request->refresh()->status);
    }

    public function test_processor_recover_orphaned_processing_row_at_start_and_processes_pending_row(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $orphanedRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
            'started_at' => now()->subMinutes(10),
        ]);
        $pendingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 2, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->with($character->id, 2)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->with([])->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once()->with(true);

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::FAILED, $orphanedRequest->refresh()->status);
        $this->assertSame(
            BattleRewardProcessingQueueManager::ORPHANED_PROCESSING_FAILED_REASON,
            $orphanedRequest->failed_reason,
        );
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $pendingRequest->refresh()->status);
    }

    public function test_processor_dispatches_top_bar_update_after_completed_battle_reward_request(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        Event::assertDispatched(UpdateTopBarEvent::class);
    }

    public function test_processor_dispatches_base_character_information_after_completed_battle_reward_request(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        Event::assertDispatched(UpdateBaseCharacterInformation::class);
    }

    public function test_processor_dispatches_exploration_output_after_completed_exploration_request(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        Event::assertDispatched(ExplorationOutputUpdated::class);
    }

    public function test_processor_dispatches_delve_status_updated_after_completed_automation_request(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::AUTOMATION,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        Event::assertDispatched(UpdateTopBarEvent::class);
        Event::assertDispatched(DelveStatusUpdated::class);
    }

    public function test_processor_dispatches_top_bar_update_once_per_completed_request(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 2, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->twice()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->twice()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->twice()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->twice();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        Event::assertDispatchedTimes(UpdateTopBarEvent::class, 2);
    }

    public function test_processor_failed_hook_marks_orphaned_processing_row_failed_and_dispatches_for_pending_rows(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
            'started_at' => now()->subMinutes(10),
        ]);
        $pendingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 2, 'context' => []],
        ]);

        (new ProcessCharacterBattleRewardQueue($character->id))->failed(
            new RuntimeException('job failed hard'),
        );

        $this->assertSame(BattleRewardRequestStatus::FAILED, $processingRequest->refresh()->status);
        $this->assertSame(
            BattleRewardProcessingQueueManager::ORPHANED_PROCESSING_FAILED_REASON,
            $processingRequest->failed_reason,
        );
        $this->assertSame(BattleRewardRequestStatus::PENDING, $pendingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_final_player_update_failure_does_not_mark_completed_reward_row_as_failed(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setUp')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once();

        Event::listen(UpdateTopBarEvent::class, function (): void {
            throw new RuntimeException('broadcast failed');
        });

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
    }

    public function test_processor_exits_without_failing_fresh_processing_row_at_processor_start(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
            'started_at' => now(),
        ]);
        $pendingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 2, 'context' => []],
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldNotReceive('withHeartbeatCallback');

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        $this->assertNull($processingRequest->failed_reason);
        $this->assertSame(BattleRewardRequestStatus::PENDING, $pendingRequest->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_failed_hook_does_not_fail_fresh_processing_row_or_dispatch_continuation(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
            'started_at' => now(),
        ]);
        $pendingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 2, 'context' => []],
        ]);

        (new ProcessCharacterBattleRewardQueue($character->id))->failed(
            new RuntimeException('job failed hard'),
        );

        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        $this->assertNull($processingRequest->failed_reason);
        $this->assertSame(BattleRewardRequestStatus::PENDING, $pendingRequest->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_job_timeout_property_is300(): void
    {
        $job = new ProcessCharacterBattleRewardQueue(1);

        $this->assertSame(300, $job->timeout);
    }

    public function test_processor_recovers_ledger_backed_processing_row_at_startup_and_processes_it(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
            'started_at' => now(),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RUNNING,
        ]);

        $battleRewardService = Mockery::mock(BattleRewardService::class);
        $battleRewardService->shouldReceive('withHeartbeatCallback')->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processLedgerAwareRewards')->once()->andThrow(
            new Exception('using legacy fallback'),
        );
        $battleRewardService->shouldReceive('setUp')->once()->with($character->id, 1)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->with([])->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once()->with(true);

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
            resolve(Manager::class),
            resolve(CharacterSheetBaseInfoTransformer::class),
            resolve(ExplorationLogService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $processingRequest->refresh()->status);
    }

    public function test_processor_failed_hook_recovers_ledger_backed_processing_row_and_dispatches_continuation(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 1, 'context' => []],
            'started_at' => now(),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RUNNING,
        ]);

        (new ProcessCharacterBattleRewardQueue($character->id))->failed(
            new RuntimeException('job failed hard'),
        );

        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }
}
