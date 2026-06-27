<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BattleRewardProcessingQueueManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reward_processing_log_channel_exists_in_config(): void
    {
        $this->assertNotNull(config('logging.channels.reward_processing'));
        $this->assertSame('daily', config('logging.channels.reward_processing.driver'));
    }

    public function test_enqueue_creates_pending_request_and_starts_one_processor(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);

        $request = $manager->enqueue(
            $character,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            44,
            ['monster_id' => 44, 'context' => ['attack_type' => 'attack']],
        );

        $this->assertSame(BattleRewardRequestStatus::PENDING, $request->status);
        $this->assertSame(['monster_id' => 44, 'context' => ['attack_type' => 'attack']], $request->handler_payload);
        $this->assertTrue(CharacterBattleRewardQueueState::where('character_id', $character->id)->firstOrFail()->is_processing);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_enqueue_does_not_dispatch_another_processor_for_active_character(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);

        $manager->enqueue($character, BattleRewardRequestPriority::FIRST, BattleRewardRequestSourceType::QUEST, 1, ['quest_id' => 1]);
        $manager->enqueue($character, BattleRewardRequestPriority::SECOND, BattleRewardRequestSourceType::BATTLE, 2, ['monster_id' => 2]);

        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
        $this->assertSame(2, CharacterBattleRewardRequest::forCharacter($character->id)->count());
    }

    public function test_different_characters_each_receive_a_processor(): void
    {
        Event::fake();
        Queue::fake();
        $firstCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $secondCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);

        $manager->enqueue($firstCharacter, BattleRewardRequestPriority::SECOND, BattleRewardRequestSourceType::BATTLE, 1, ['monster_id' => 1]);
        $manager->enqueue($secondCharacter, BattleRewardRequestPriority::SECOND, BattleRewardRequestSourceType::BATTLE, 2, ['monster_id' => 2]);

        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 2);
    }

    public function test_existing_state_from_duplicate_first_creation_is_fetched_and_locked(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => false,
        ]);

        $started = resolve(BattleRewardProcessingQueueManager::class)
            ->ensureProcessorRunning($character);

        $this->assertTrue($started);
        $this->assertSame(
            1,
            CharacterBattleRewardQueueState::where('character_id', $character->id)->count(),
        );
        $this->assertTrue(
            CharacterBattleRewardQueueState::where('character_id', $character->id)
                ->firstOrFail()
                ->is_processing,
        );
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_next_request_uses_priority_then_id_order(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);

        $second = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::SECOND,
        ]);
        $firstOldest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::FIRST,
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::FIRST,
        ]);

        $this->assertSame($firstOldest->id, $manager->nextRequest($character->id)?->id);
        $this->assertSame($second->id, CharacterBattleRewardRequest::findOrFail($second->id)->id);
    }

    public function test_resumable_interrupted_request_is_claimed_before_new_pending_quest(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);
        $resumable = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::RESUMABLE,
            'priority' => BattleRewardRequestPriority::SECOND,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'priority' => BattleRewardRequestPriority::FIRST,
            'source_type' => BattleRewardRequestSourceType::QUEST,
        ]);

        $claimed = $manager->nextRequest($character->id);

        $this->assertSame($resumable->id, $claimed?->id);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $claimed?->status);
    }

    public function test_quest_priority_beats_lower_priority_backlog_after_resumable_request_completes(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);
        $resumable = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::RESUMABLE,
            'priority' => BattleRewardRequestPriority::SECOND,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
        ]);
        $backlog = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'priority' => BattleRewardRequestPriority::THIRD,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
        ]);
        $quest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'priority' => BattleRewardRequestPriority::FIRST,
            'source_type' => BattleRewardRequestSourceType::QUEST,
        ]);

        $manager->markCompleted($manager->nextRequest($character->id));
        $claimedQuest = $manager->nextRequest($character->id);
        $manager->markCompleted($claimedQuest);
        $claimedBacklog = $manager->nextRequest($character->id);

        $this->assertSame($resumable->id, CharacterBattleRewardRequest::findOrFail($resumable->id)->id);
        $this->assertSame($quest->id, $claimedQuest?->id);
        $this->assertSame($backlog->id, $claimedBacklog?->id);
    }

    public function test_failed_and_completed_requests_are_retained(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);
        $failed = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        $completed = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);

        $manager->markFailed($failed, new RuntimeException('exact failure'));
        $manager->markCompleted($completed);

        $this->assertStringContainsString('exact failure', $failed->refresh()->failed_reason);
        $this->assertSame(BattleRewardRequestStatus::FAILED, $failed->status);
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $completed->refresh()->status);
        $this->assertSame(2, CharacterBattleRewardRequest::forCharacter($character->id)->count());
    }

    public function test_mark_processing_refreshes_heartbeat_before_reward_is_processed(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $state = CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(3),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::SECOND,
        ]);

        resolve(BattleRewardProcessingQueueManager::class)->nextRequest($character->id);

        $this->assertTrue($state->refresh()->heartbeat_at->isAfter(now()->subSeconds(5)));
    }

    public function test_mark_completed_refreshes_heartbeat_after_reward_is_processed(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $state = CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(3),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
        ]);

        resolve(BattleRewardProcessingQueueManager::class)->markCompleted($request);

        $this->assertTrue($state->refresh()->heartbeat_at->isAfter(now()->subSeconds(5)));
    }

    public function test_admin_broadcast_event_is_dispatched_when_mark_completed_succeeds(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);

        resolve(BattleRewardProcessingQueueManager::class)->markCompleted($request);

        Event::assertDispatched(
            BattleRewardQueueUpdated::class,
            fn ($e) => $e->characterId === $character->id && $e->change === 'completed',
        );
    }

    public function test_mark_completed_status_persists_when_admin_broadcast_dispatch_throws(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create(['character_id' => $character->id]);
        Event::listen(BattleRewardQueueUpdated::class, function (): void {
            throw new RuntimeException('broadcast queue unavailable');
        });

        resolve(BattleRewardProcessingQueueManager::class)->markCompleted($request);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
    }

    public function test_enqueue_does_not_recover_processing_request_when_heartbeat_is_fresh(): void
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
            'started_at' => now(),
        ]);

        $started = resolve(BattleRewardProcessingQueueManager::class)
            ->ensureProcessorRunning($character);

        $this->assertFalse($started);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        $this->assertNull($processingRequest->failed_reason);
        Queue::assertNothingPushed();
    }

    public function test_enqueue_does_not_recover_processing_request_when_heartbeat_is_stal_but_lock_is_held(): void
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
            'started_at' => now()->subMinutes(10),
        ]);
        $lock = Cache::lock('character-reward-queue:'.$character->id, 60);
        $lock->get();

        $started = resolve(BattleRewardProcessingQueueManager::class)
            ->ensureProcessorRunning($character);

        $lock->release();

        $this->assertFalse($started);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        $this->assertNull($processingRequest->failed_reason);
        Queue::assertNothingPushed();
    }

    public function test_ensure_processor_running_recovers_orphaned_request_when_heartbeat_is_stale_and_no_lock_held(): void
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
            'started_at' => now()->subMinutes(10),
        ]);

        $started = resolve(BattleRewardProcessingQueueManager::class)
            ->ensureProcessorRunning($character);

        $this->assertTrue($started);
        $this->assertSame(BattleRewardRequestStatus::FAILED, $processingRequest->refresh()->status);
        $this->assertSame(
            BattleRewardProcessingQueueManager::ORPHANED_PROCESSING_FAILED_REASON,
            $processingRequest->failed_reason,
        );
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_pending_rows_are_woken_immediately_after_orphaned_request_recovery(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now()->subMinutes(10),
        ]);
        $pendingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'handler_payload' => ['monster_id' => 5, 'context' => []],
        ]);

        resolve(BattleRewardProcessingQueueManager::class)->ensureProcessorRunning($character);

        $this->assertSame(BattleRewardRequestStatus::PENDING, $pendingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_enqueue_recovers_stal_orphaned_request_when_enqueueing_new_reward(): void
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
            'started_at' => now()->subMinutes(10),
        ]);

        resolve(BattleRewardProcessingQueueManager::class)->enqueue(
            $character,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            55,
            ['monster_id' => 55, 'context' => []],
        );

        $this->assertSame(BattleRewardRequestStatus::FAILED, $processingRequest->refresh()->status);
        $this->assertSame(
            BattleRewardProcessingQueueManager::ORPHANED_PROCESSING_FAILED_REASON,
            $processingRequest->failed_reason,
        );
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_enqueue_does_not_dispatch_duplicate_processor_when_character_lock_is_held(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);
        $lock = Cache::lock('character-reward-queue:'.$character->id, 60);
        $lock->get();

        $manager->enqueue(
            $character,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            44,
            ['monster_id' => 44, 'context' => []],
        );

        $lock->release();

        Queue::assertNothingPushed();
        $this->assertSame(BattleRewardRequestStatus::PENDING, CharacterBattleRewardRequest::forCharacter($character->id)->firstOrFail()->status);
    }

    public function test_different_characters_processing_rows_do_not_block_each_other(): void
    {
        Event::fake();
        Queue::fake();
        $firstCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $secondCharacter = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $firstCharacter->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $firstCharacter->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
        ]);

        $manager = resolve(BattleRewardProcessingQueueManager::class);
        $manager->enqueue(
            $secondCharacter,
            BattleRewardRequestPriority::SECOND,
            BattleRewardRequestSourceType::BATTLE,
            99,
            ['monster_id' => 99],
        );

        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
        $this->assertTrue(
            CharacterBattleRewardQueueState::where('character_id', $secondCharacter->id)->firstOrFail()->is_processing,
        );
    }

    public function test_recover_orphaned_processing_requests_returns_zero_when_heartbeat_is_fresh(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now(),
        ]);

        $recovered = resolve(BattleRewardProcessingQueueManager::class)
            ->recoverOrphanedProcessingRequests($character->id);

        $this->assertSame(0, $recovered);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        $this->assertNull($processingRequest->failed_reason);
    }

    public function test_recover_orphaned_processing_requests_fails_rows_when_heartbeat_is_stale(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now()->subMinutes(10),
        ]);

        $recovered = resolve(BattleRewardProcessingQueueManager::class)
            ->recoverOrphanedProcessingRequests($character->id);

        $this->assertSame(1, $recovered);
        $this->assertSame(BattleRewardRequestStatus::FAILED, $processingRequest->refresh()->status);
        $this->assertSame(
            BattleRewardProcessingQueueManager::ORPHANED_PROCESSING_FAILED_REASON,
            $processingRequest->failed_reason,
        );
    }

    public function test_recover_orphaned_processing_requests_returns_zero_when_queue_state_is_missing(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now()->subMinutes(10),
        ]);

        $recovered = resolve(BattleRewardProcessingQueueManager::class)
            ->recoverOrphanedProcessingRequests($character->id);

        $this->assertSame(0, $recovered);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
    }

    public function test_ensure_processor_running_wakes_for_fresh_heartbeat_with_ledger_backed_processing_row(): void
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
            'started_at' => now(),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RUNNING,
        ]);

        $started = resolve(BattleRewardProcessingQueueManager::class)
            ->ensureProcessorRunning($character);

        $this->assertTrue($started);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function test_recover_ledger_backed_processing_requests_marks_running_step_and_request_resumable(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now(),
        ]);
        $runningStep = CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RUNNING,
        ]);

        $recovered = resolve(BattleRewardProcessingQueueManager::class)
            ->recoverLedgerBackedProcessingRequests($character->id);

        $this->assertSame(1, $recovered);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        $this->assertSame(BattleRewardStepStatus::RESUMABLE, $runningStep->refresh()->status);
    }

    public function test_recover_ledger_backed_processing_requests_ignores_legacy_rows_with_no_steps(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $legacyRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now(),
        ]);

        $recovered = resolve(BattleRewardProcessingQueueManager::class)
            ->recoverLedgerBackedProcessingRequests($character->id);

        $this->assertSame(0, $recovered);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $legacyRequest->refresh()->status);
    }

    public function test_recover_ledger_backed_processing_requests_marks_checkpointed_step_resumable(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $processingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now(),
        ]);
        $checkpointedStep = CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::CHECKPOINTED,
            'checkpoint_json' => ['remaining_xp' => 250],
        ]);

        resolve(BattleRewardProcessingQueueManager::class)
            ->recoverLedgerBackedProcessingRequests($character->id);

        $this->assertSame(BattleRewardStepStatus::RESUMABLE, $checkpointedStep->refresh()->status);
        $this->assertSame(['remaining_xp' => 250], $checkpointedStep->refresh()->checkpoint_json);
    }

    public function test_force_release_processor_lock_releases_held_lock(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $lock = Cache::lock('character-reward-queue:'.$character->id, 1800);
        $lock->get();
        $this->assertTrue(
            resolve(BattleRewardProcessingQueueManager::class)->isProcessorLocked($character->id),
            'Lock should be held before force release',
        );

        resolve(BattleRewardProcessingQueueManager::class)->forceReleaseProcessorLock($character->id);

        $this->assertFalse(
            resolve(BattleRewardProcessingQueueManager::class)->isProcessorLocked($character->id),
            'Lock should be free after force release',
        );
    }
}
