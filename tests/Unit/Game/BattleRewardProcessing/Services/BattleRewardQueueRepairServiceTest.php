<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardQueueUpdated;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\BattleRewardProcessing\Services\BattleRewardQueueRepairService;
use App\Game\BattleRewardProcessing\Services\BattleRewardResumeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BattleRewardQueueRepairServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testRepairRestartsOneProcessorWhenPendingRowsExist(): void
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
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $summary = resolve(BattleRewardQueueRepairService::class)->repairStaleQueues();

        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
        $this->assertSame(1, $summary['restarted_processor_count']);
        $this->assertTrue(
            CharacterBattleRewardQueueState::where('character_id', $character->id)
                ->firstOrFail()
                ->is_processing,
        );
    }

    public function testRepairMarksLegacyStaleProcessingRowsFailedWithExactReason(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
        ]);

        resolve(BattleRewardQueueRepairService::class)->repairStaleQueues();

        $request->refresh();
        $this->assertSame(BattleRewardRequestStatus::FAILED, $request->status);
        $this->assertSame(BattleRewardResumeService::LEGACY_FAILED_REASON, $request->failed_reason);
    }

    public function testRepairLeavesPendingRowsPending(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => null,
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        resolve(BattleRewardQueueRepairService::class)->repairStaleQueues();

        $this->assertSame(BattleRewardRequestStatus::PENDING, $request->refresh()->status);
    }

    public function testRepairClearsStaleStateWhenNoPendingRowsExist(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'started_at' => now()->subMinutes(20),
            'heartbeat_at' => now()->subMinutes(10),
        ]);

        $summary = resolve(BattleRewardQueueRepairService::class)->repairStaleQueues();

        $state = CharacterBattleRewardQueueState::where('character_id', $character->id)->firstOrFail();
        $this->assertFalse($state->is_processing);
        $this->assertNull($state->started_at);
        $this->assertNull($state->heartbeat_at);
        $this->assertSame(1, $summary['cleared_inactive_queue_state_count']);
        Queue::assertNothingPushed();
    }

    public function testRepairDoesNotTouchNonStaleActiveState(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $state = CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now(),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
        ]);

        $summary = resolve(BattleRewardQueueRepairService::class)->repairStaleQueues();

        $this->assertTrue($state->refresh()->is_processing);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $request->refresh()->status);
        $this->assertSame(0, $summary['repaired_queue_state_count']);
        Queue::assertNothingPushed();
    }

    public function testRepairDoesNotRetryExistingFailedRows(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $request = CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::FAILED,
            'failed_reason' => 'Original failure',
        ]);

        resolve(BattleRewardQueueRepairService::class)->repairStaleQueues();

        $request->refresh();
        $this->assertSame(BattleRewardRequestStatus::FAILED, $request->status);
        $this->assertSame('Original failure', $request->failed_reason);
    }

    public function testRepairBroadcastsQueueUpdate(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);

        resolve(BattleRewardQueueRepairService::class)->repairStaleQueues();

        Event::assertDispatched(
            BattleRewardQueueUpdated::class,
            fn (BattleRewardQueueUpdated $event): bool => $event->characterId === $character->id
                && $event->change === BattleRewardQueueUpdated::REPAIRED,
        );
    }

    public function testRepairReturnsAllSummaryCounts(): void
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
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $summary = resolve(BattleRewardQueueRepairService::class)->repairStaleQueues();

        $this->assertSame([
            'repaired_queue_state_count' => 1,
            'resumed_processing_request_count' => 0,
            'legacy_failed_processing_request_count' => 1,
            'restarted_processor_count' => 1,
            'cleared_inactive_queue_state_count' => 0,
            'resumable_step_count' => 0,
            'unemitted_message_count' => 0,
            'would_resume_processing_request_count' => 0,
            'would_legacy_fail_processing_request_count' => 0,
        ], $summary);
    }
}
