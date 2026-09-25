<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Events\BattleRewardProgressionUpdated;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardPresentationQueue;
use App\Game\BattleRewardProcessing\Services\BattleRewardPresentationQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardPresentationService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;

class ProcessCharacterBattleRewardPresentationQueueTest extends TestCase
{
    use CreateCharacterBattleReward, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_exits_without_presenting_when_another_worker_holds_the_presentation_lock(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);
        $heldLock = (new BattleRewardPresentationQueueManager)->processorLock($character->id);
        $heldLock->get();

        ProcessCharacterBattleRewardPresentationQueue::dispatch($character->id);

        $heldLock->release();

        $this->assertSame(
            [BattleRewardStepStatus::PENDING, BattleRewardStepStatus::PENDING],
            $request->steps()->orderBy('id')->pluck('status')->all(),
        );
        Event::assertNotDispatched(BattleRewardProgressionUpdated::class);
    }

    public function test_drains_pending_presentation_oldest_first_and_leaves_the_requests_completed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $olderRequest = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $newerRequest = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $olderRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $olderRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $newerRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $newerRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $newerRequest->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'message' => 'newer request message',
        ]);
        $this->createCharacterBattleRewardRequestMessage([
            'character_battle_reward_request_id' => $olderRequest->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'message' => 'older request message',
        ]);
        $emittedMessages = [];
        Event::listen(ServerMessageEvent::class, function (ServerMessageEvent $event) use (&$emittedMessages): void {
            $emittedMessages[] = $event->message;
        });

        ProcessCharacterBattleRewardPresentationQueue::dispatch($character->id);

        $this->assertSame(['older request message', 'newer request message'], $emittedMessages);
        $this->assertSame(
            2,
            CharacterBattleRewardRequest::whereIn('id', [$olderRequest->id, $newerRequest->id])
                ->where('status', BattleRewardRequestStatus::COMPLETED)
                ->count(),
        );
    }

    public function test_clears_the_progression_overlay_once_the_lane_is_drained(): void
    {
        Event::fake([BattleRewardProgressionUpdated::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);

        ProcessCharacterBattleRewardPresentationQueue::dispatch($character->id);

        Event::assertDispatched(
            BattleRewardProgressionUpdated::class,
            fn (BattleRewardProgressionUpdated $event): bool => $event->complete
                && $event->requestId === 0
                && is_null($event->level)
                && $event->broadcastOn()->name === 'private-battle-reward-progression-'.$character->user_id,
        );
    }

    public function test_presentation_work_completed_while_the_drained_lane_still_holds_the_lock_is_presented_by_a_continuation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $initialRequest = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $initialRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $initialRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);
        $emittedMessages = [];
        Event::listen(ServerMessageEvent::class, function (ServerMessageEvent $event) use (&$emittedMessages): void {
            $emittedMessages[] = $event->message;
        });
        $lateRequest = null;
        $lateRequestCreated = false;
        Event::listen(BattleRewardProgressionUpdated::class, function (BattleRewardProgressionUpdated $event) use ($character, &$lateRequest, &$lateRequestCreated): void {
            if (! $event->complete || $lateRequestCreated) {
                return;
            }

            $lateRequestCreated = true;
            $lateRequest = $this->createCharacterBattleRewardRequest([
                'character_id' => $character->id,
                'source_type' => BattleRewardRequestSourceType::QUEST,
                'status' => BattleRewardRequestStatus::COMPLETED,
            ]);
            $this->createCharacterBattleRewardRequestStep([
                'character_battle_reward_request_id' => $lateRequest->id,
                'character_id' => $character->id,
                'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
                'status' => BattleRewardStepStatus::COMPLETED,
            ]);
            $this->createCharacterBattleRewardRequestStep([
                'character_battle_reward_request_id' => $lateRequest->id,
                'character_id' => $character->id,
                'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
            ]);
            $this->createCharacterBattleRewardRequestMessage([
                'character_battle_reward_request_id' => $lateRequest->id,
                'character_id' => $character->id,
                'user_id' => $character->user_id,
                'message' => 'late request message',
            ]);

            ProcessCharacterBattleRewardPresentationQueue::dispatch($character->id);
        });

        ProcessCharacterBattleRewardPresentationQueue::dispatch($character->id);

        $this->assertSame(['late request message'], $emittedMessages);
        $this->assertSame(
            [BattleRewardStepStatus::COMPLETED, BattleRewardStepStatus::COMPLETED],
            $lateRequest->steps()->orderBy('id')->pluck('status')->all(),
        );
    }

    public function test_presentation_failure_keeps_the_request_completed_and_does_not_clear_the_overlay(): void
    {
        Event::fake([BattleRewardProgressionUpdated::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
        ]);
        $presentationService = Mockery::mock(BattleRewardPresentationService::class);
        $presentationService->shouldReceive('present')->once()->andThrow(new RuntimeException('presentation failed'));
        $this->app->instance(BattleRewardPresentationService::class, $presentationService);

        try {
            ProcessCharacterBattleRewardPresentationQueue::dispatch($character->id);
        } catch (RuntimeException $exception) {
            $this->assertSame('presentation failed', $exception->getMessage());
        }

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
        Event::assertNotDispatched(BattleRewardProgressionUpdated::class);
    }
}
