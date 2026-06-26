<?php

namespace Tests\Console\BattleRewardProcessing;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ResumeInterruptedRewardProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function testCommandExitsZeroWhenNoStaleQueues(): void
    {
        Event::fake();
        Queue::fake();

        $exitCode = $this->artisan('reward-processing:resume-interrupted --apply');

        $this->assertEquals(0, $exitCode);
        Queue::assertNothingPushed();
    }

    public function testCommandDryRunExitsZeroWithStaleStateAndDoesNotDispatch(): void
    {
        Event::fake();
        Queue::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);

        $exitCode = $this->artisan('reward-processing:resume-interrupted');

        $this->assertEquals(0, $exitCode);
        Queue::assertNothingPushed();
    }

    public function testCommandApplyModeDispatchesProcessorForStaleQueue(): void
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

        $exitCode = $this->artisan('reward-processing:resume-interrupted --apply');

        $this->assertEquals(0, $exitCode);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testCommandApplyAloneRecoversFreshHeartbeatLedgerBackedRow(): void
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

        $exitCode = $this->artisan('reward-processing:resume-interrupted --apply');

        $this->assertEquals(0, $exitCode);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testCommandForceModeApplyRecoversFreshHeartbeatLedgerBackedRow(): void
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

        $exitCode = $this->artisan('reward-processing:resume-interrupted --force --apply');

        $this->assertEquals(0, $exitCode);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testCommandForceModeWithCharacterIdScopesRecovery(): void
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

        $exitCode = $this->artisan("reward-processing:resume-interrupted --force --apply --character_id={$target->id}");

        $this->assertEquals(0, $exitCode);
        $this->assertTrue(
            CharacterBattleRewardRequest::forCharacter($other->id)->processing()->exists(),
            'Other character must not be touched',
        );
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testCommandForceDryRunDoesNotChangeStatusOrDispatch(): void
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

        $exitCode = $this->artisan('reward-processing:resume-interrupted --force');

        $this->assertEquals(0, $exitCode);
        $this->assertSame(BattleRewardRequestStatus::PROCESSING, $processingRequest->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function testCommandApplyRecoversMissingQueueState(): void
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

        $exitCode = $this->artisan('reward-processing:resume-interrupted --apply');

        $this->assertEquals(0, $exitCode);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        $this->assertTrue(CharacterBattleRewardQueueState::where('character_id', $character->id)->exists());
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testCommandApplyRecoverInactiveQueueState(): void
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

        $exitCode = $this->artisan('reward-processing:resume-interrupted --apply');

        $this->assertEquals(0, $exitCode);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        $this->assertTrue(
            CharacterBattleRewardQueueState::where('character_id', $character->id)
                ->where('is_processing', true)
                ->exists(),
        );
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }

    public function testCommandApplyReleasesOrphanedLockAndRecovers(): void
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
        $lock = \Illuminate\Support\Facades\Cache::lock('character-reward-queue:' . $character->id, 1800);
        $lock->get();

        $exitCode = $this->artisan('reward-processing:resume-interrupted --apply');

        $this->assertEquals(0, $exitCode);
        $this->assertSame(BattleRewardRequestStatus::RESUMABLE, $processingRequest->refresh()->status);
        Queue::assertPushed(ProcessCharacterBattleRewardQueue::class, 1);
    }
}
