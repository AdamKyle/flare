<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\BattleRewardProcessing\Services\BattleRewardResumeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BattleRewardResumeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testResumeAllReturnsSummaryWithExpectedKeys(): void
    {
        Event::fake();
        Queue::fake();

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertArrayHasKey('recovered_processing_request_count', $summary);
        $this->assertArrayHasKey('would_recover_processing_request_count', $summary);
        $this->assertArrayHasKey('pending_only_lane_wake_count', $summary);
        $this->assertArrayHasKey('would_pending_only_lane_wake_count', $summary);
        $this->assertArrayHasKey('inactive_queue_state_count', $summary);
        $this->assertArrayHasKey('would_inactive_queue_state_count', $summary);
        $this->assertArrayHasKey('legacy_failed_processing_request_count', $summary);
        $this->assertArrayHasKey('legacy_skipped_processing_request_count', $summary);
        $this->assertArrayHasKey('locked_skipped_count', $summary);
        $this->assertArrayHasKey('locked_recovery_blocked_count', $summary);
        $this->assertArrayHasKey('released_lock_count', $summary);
        $this->assertArrayHasKey('would_release_lock_count', $summary);
        $this->assertArrayHasKey('restarted_processor_count', $summary);
        $this->assertArrayHasKey('resumable_step_count', $summary);
        $this->assertArrayHasKey('unemitted_message_count', $summary);
    }

    public function testResumeAllApplyRecoversFreshHeartbeatLedgerBackedRow(): void
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

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['recovered_processing_request_count']);
        $this->assertSame(0, $summary['would_recover_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testResumeAllApplyRecoversStaleLedgerBackedRow(): void
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
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RUNNING,
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['recovered_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testResumeAllDryRunReturnsWouldRecoverWithoutChangingStatus(): void
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

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(false, null);

        $this->assertSame(1, $summary['would_recover_processing_request_count']);
        $this->assertSame(0, $summary['recovered_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function testResumeAllApplyMarksRunningAndCheckpointedStepsResumable(): void
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
        $runningStep = CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RUNNING,
        ]);
        $checkpointedStep = CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::CHECKPOINTED,
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(2, $summary['resumable_step_count']);
        $this->assertSame(BattleRewardStepStatus::RESUMABLE, $runningStep->refresh()->status);
        $this->assertSame(BattleRewardStepStatus::RESUMABLE, $checkpointedStep->refresh()->status);
    }

    public function testResumeAllApplyWakesPendingOnlyLane(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $pendingRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['pending_only_lane_wake_count']);
        $this->assertSame(0, $summary['recovered_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::PENDING, $pendingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testResumeAllDryRunCountsWouldWakePendingOnlyLane(): void
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
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(false, null);

        $this->assertSame(1, $summary['would_pending_only_lane_wake_count']);
        $this->assertSame(0, $summary['pending_only_lane_wake_count']);
        Queue::assertNothingPushed();
    }

    public function testResumeAllApplyMarksEmptyLaneInactive(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $state = CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['inactive_queue_state_count']);
        $this->assertFalse($state->refresh()->is_processing);
        Queue::assertNothingPushed();
    }

    public function testResumeAllDryRunCountsWouldMarkInactive(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $state = CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(false, null);

        $this->assertSame(1, $summary['would_inactive_queue_state_count']);
        $this->assertSame(0, $summary['inactive_queue_state_count']);
        $this->assertTrue($state->refresh()->is_processing);
    }

    public function testResumeAllSkipsFreshLegacyRowWithNoSteps(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $legacyRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now(),
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['legacy_skipped_processing_request_count']);
        $this->assertSame(0, $summary['recovered_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $legacyRequest->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function testResumeAllFailsStaleLegacyRowWithNoSteps(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $legacyRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now()->subMinutes(10),
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['legacy_failed_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::FAILED, $legacyRequest->refresh()->status);
        $this->assertSame(BattleRewardResumeService::LEGACY_FAILED_REASON, $legacyRequest->refresh()->failed_reason);
    }

    public function testResumeAllApplyReleasesOrphanedLockForLedgerBackedRow(): void
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
        $lock = Cache::lock('character-reward-queue:' . $character->id, 1800);
        $lock->get();

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['released_lock_count']);
        $this->assertSame(1, $summary['recovered_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testResumeAllDryRunReportsWouldReleaseLockWithoutReleasing(): void
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
        $lock = Cache::lock('character-reward-queue:' . $character->id, 1800);
        $lock->get();

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(false, null);

        $lock->release();

        $this->assertSame(1, $summary['would_release_lock_count']);
        $this->assertSame(0, $summary['released_lock_count']);
        $this->assertSame(0, $summary['would_recover_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function testResumeAllDoesNotReleaseLockForLegacyLockedRow(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $legacyRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now(),
        ]);
        $lock = Cache::lock('character-reward-queue:' . $character->id, 1800);
        $lock->get();

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $lock->release();

        $this->assertSame(0, $summary['released_lock_count']);
        $this->assertSame(1, $summary['locked_recovery_blocked_count']);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $legacyRequest->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function testResumeAllDoesNotReleaseLockForEmptyLockedLane(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $lock = Cache::lock('character-reward-queue:' . $character->id, 1800);
        $lock->get();

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $lock->release();

        $this->assertSame(0, $summary['released_lock_count']);
        $this->assertSame(1, $summary['locked_recovery_blocked_count']);
        Queue::assertNothingPushed();
    }

    public function testResumeAllApplyRecoversMissingQueueState(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
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

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['recovered_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        $this->assertTrue(CharacterBattleRewardQueueState::where('character_id', $character->id)->exists());
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testResumeAllApplyRecoverInactiveQueueState(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => false,
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

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['recovered_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        $this->assertTrue(
            CharacterBattleRewardQueueState::where('character_id', $character->id)
                ->where('is_processing', true)
                ->exists(),
        );
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testResumeAllApplyRecoversMissingQueueStateForResumableRequest(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $resumableRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::RESUMABLE,
            'started_at' => now(),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $resumableRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RESUMABLE,
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['pending_only_lane_wake_count']);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testResumeAllRunningTwiceDoesNotDispatchUnsafeDuplicateProcessors(): void
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

        resolve(BattleRewardResumeService::class)->resumeAll(true, null);
        resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 2);
    }

    public function testResumeAllSkipsLockedCharacterWithLegacyRow(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $legacyRequest = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now(),
        ]);
        $lock = Cache::lock('character-reward-queue:' . $character->id, 1800);
        $lock->get();

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $lock->release();

        $this->assertSame(1, $summary['locked_recovery_blocked_count']);
        $this->assertSame(0, $summary['recovered_processing_request_count']);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $legacyRequest->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function testResumeAllScopesToCharacterId(): void
    {
        Event::fake();
        Queue::fake();
        $target = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $other = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        foreach ([$target, $other] as $character) {
            CharacterBattleRewardQueueState::factory()->create([
                'character_id' => $character->id,
                'is_processing' => true,
                'heartbeat_at' => now(),
            ]);
            $request = CharacterBattleRewardRequest::factory()->create([
                'character_id' => $character->id,
                'status' => BattleRewardRequestStatus::PROCESSING,
                'started_at' => now(),
            ]);
            CharacterBattleRewardRequestStep::factory()->create([
                'character_battle_reward_request_id' => $request->id,
                'character_id' => $character->id,
                'step_name' => BattleRewardStepName::XP,
                'status' => BattleRewardStepStatus::RUNNING,
            ]);
        }

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, $target->id);

        $this->assertSame(1, $summary['recovered_processing_request_count']);
        $this->assertTrue(
            CharacterBattleRewardRequest::forCharacter($other->id)->processing()->exists(),
            'Other character processing row must not be touched',
        );
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testResumeAllBroadcastsRepairEvent(): void
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

        resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        Event::assertDispatched(
            BattleRewardQueueUpdated::class,
            fn (BattleRewardQueueUpdated $event): bool => $event->characterId === $character->id
                && $event->change === BattleRewardQueueUpdated::REPAIRED,
        );
    }

    public function testResumeAllHandlesMultipleCharactersIndependently(): void
    {
        Event::fake();
        Queue::fake();
        $characterA = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $characterB = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $characterA->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $requestA = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $characterA->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
            'started_at' => now(),
        ]);
        CharacterBattleRewardRequestStep::factory()->create([
            'character_battle_reward_request_id' => $requestA->id,
            'character_id' => $characterA->id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RUNNING,
        ]);

        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $characterB->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $characterB->id,
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['recovered_processing_request_count']);
        $this->assertSame(1, $summary['pending_only_lane_wake_count']);
        $this->assertSame(2, $summary['restarted_processor_count']);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 2);
    }

    public function testResumeAllCountsUnemittedMessages(): void
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
        \App\Flare\Models\CharacterBattleRewardRequestMessage::factory()->create([
            'character_battle_reward_request_id' => $processingRequest->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'step_name' => BattleRewardStepName::XP,
            'emitted_at' => null,
        ]);

        $summary = resolve(BattleRewardResumeService::class)->resumeAll(true, null);

        $this->assertSame(1, $summary['unemitted_message_count']);
    }
}
