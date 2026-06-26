<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
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

    public function testRewardProcessingLogChannelExistsInConfig(): void
    {
        $this->assertNotNull(config('logging.channels.reward_processing'));
        $this->assertSame('daily', config('logging.channels.reward_processing.driver'));
    }

    public function testEnqueueCreatesPendingRequestAndStartsOneProcessor(): void
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

    public function testEnqueueDoesNotDispatchAnotherProcessorForActiveCharacter(): void
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

    public function testDifferentCharactersEachReceiveAProcessor(): void
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

    public function testExistingStateFromDuplicateFirstCreationIsFetchedAndLocked(): void
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

    public function testNextRequestUsesPriorityThenIdOrder(): void
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

    public function testResumableInterruptedRequestIsClaimedBeforeNewPendingQuest(): void
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

    public function testQuestPriorityBeatsLowerPriorityBacklogAfterResumableRequestCompletes(): void
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

    public function testFailedAndCompletedRequestsAreRetained(): void
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

    public function testMarkProcessingRefreshesHeartbeatBeforeRewardIsProcessed(): void
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

    public function testMarkCompletedRefreshesHeartbeatAfterRewardIsProcessed(): void
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

    public function testAdminBroadcastEventIsDispatchedWhenMarkCompletedSucceeds(): void
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

    public function testMarkCompletedStatusPersistsWhenAdminBroadcastDispatchThrows(): void
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

    public function testEnqueueDoesNotRecoverProcessingRequestWhenHeartbeatIsFresh(): void
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

    public function testEnqueueDoesNotRecoverProcessingRequestWhenHeartbeatIsStalButLockIsHeld(): void
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
        $lock = Cache::lock('character-reward-queue:' . $character->id, 60);
        $lock->get();

        $started = resolve(BattleRewardProcessingQueueManager::class)
            ->ensureProcessorRunning($character);

        $lock->release();

        $this->assertFalse($started);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        $this->assertNull($processingRequest->failed_reason);
        Queue::assertNothingPushed();
    }

    public function testEnsureProcessorRunningRecoversOrphanedRequestWhenHeartbeatIsStaleAndNoLockHeld(): void
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

    public function testPendingRowsAreWokenImmediatelyAfterOrphanedRequestRecovery(): void
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

    public function testEnqueueRecoversStalOrphanedRequestWhenEnqueueingNewReward(): void
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

    public function testEnqueueDoesNotDispatchDuplicateProcessorWhenCharacterLockIsHeld(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $manager = resolve(BattleRewardProcessingQueueManager::class);
        $lock = Cache::lock('character-reward-queue:' . $character->id, 60);
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

    public function testDifferentCharactersProcessingRowsDoNotBlockEachOther(): void
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

    public function testRecoverOrphanedProcessingRequestsReturnsZeroWhenHeartbeatIsFresh(): void
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

    public function testRecoverOrphanedProcessingRequestsFailsRowsWhenHeartbeatIsStale(): void
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

    public function testRecoverOrphanedProcessingRequestsReturnsZeroWhenQueueStateIsMissing(): void
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
}
