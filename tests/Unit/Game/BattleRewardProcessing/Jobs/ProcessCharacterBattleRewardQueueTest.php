<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\Automation\Delve\Events\DelveStatusUpdated;
use App\Game\Automation\Exploration\Events\ExplorationOutputUpdated;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Jobs\DispatchBattleRewardSecondaryUpdates;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardMessageOutboxService;
use App\Game\BattleRewardProcessing\Services\BattleRewardSharedContextService;
use App\Game\BattleRewardProcessing\Services\BattleRewardStepPlanService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Services\DropCheckService;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateQuest;

class ProcessCharacterBattleRewardQueueTest extends TestCase
{
    use CreateCharacterBattleReward, CreateGuideQuest, CreateItem, CreateMonster, CreateQuest, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_dispatching_the_processor_completes_a_pending_battle_request(): void
    {
        Event::fake();
        $this->createItem(['type' => 'weapon', 'skill_level_required' => 0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
    }

    public function test_dispatching_the_processor_resumes_a_request_with_already_completed_steps(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::RESUMABLE,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request, resolve(BattleRewardStepPlanService::class)->planBattleLike($request, resolve(BattleRewardSharedContextService::class)->build($request, $character, $monster)));
        $request->steps()
            ->whereNotIn('step_name', [BattleRewardStepName::FINAL_PLAYER_UPDATES, BattleRewardStepName::MESSAGE_OUTBOX])
            ->update(['status' => BattleRewardStepStatus::COMPLETED]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
        $this->assertSame(
            BattleRewardStepStatus::COMPLETED,
            $request->steps()->where('step_name', BattleRewardStepName::FINAL_PLAYER_UPDATES)->firstOrFail()->status,
        );
    }

    public function test_a_step_failure_marks_the_request_failed(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        $dropCheckService = Mockery::mock(DropCheckService::class);
        $dropCheckService->shouldReceive('planDrops')->andThrow(new RuntimeException('drop plan boom'));
        $this->app->instance(DropCheckService::class, $dropCheckService);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::FAILED, $request->refresh()->status);
        $this->assertSame(
            BattleRewardStepStatus::FAILED,
            $request->steps()->where('step_name', BattleRewardStepName::ITEM_DROPS)->firstOrFail()->status,
        );
    }

    public function test_a_missing_monster_fails_the_request_at_the_job_boundary(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => 999999999, 'context' => []],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $request = $request->refresh();
        $this->assertSame(BattleRewardRequestStatus::FAILED, $request->status);
        $this->assertStringContainsString('Unable to build battle reward context', $request->failed_reason);
    }

    public function test_a_future_source_request_fails_at_the_job_boundary(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::FUTURE,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => [],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $request = $request->refresh();
        $this->assertSame(BattleRewardRequestStatus::FAILED, $request->status);
        $this->assertStringContainsString('No ledger step plan exists for future reward requests.', $request->failed_reason);
    }

    public function test_completed_reward_queues_secondary_updates_instead_of_dispatching_them_inside_the_processor(): void
    {
        Event::fake();
        Queue::fake([DispatchBattleRewardSecondaryUpdates::class]);
        $this->createItem(['type' => 'weapon', 'skill_level_required' => 0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
        Event::assertDispatched(UpdateBaseCharacterInformation::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
        Event::assertNotDispatched(UpdateCharacterCurrenciesEvent::class);
        Queue::assertPushed(DispatchBattleRewardSecondaryUpdates::class, function (DispatchBattleRewardSecondaryUpdates $job) use ($character): bool {
            return $job->uniqueId() === 'battle-reward-secondary:'.$character->id;
        });
    }

    public function test_a_processor_slice_with_multiple_requests_flushes_secondary_updates_exactly_once(): void
    {
        Event::fake();
        $this->createItem(['type' => 'weapon', 'skill_level_required' => 0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $firstRequest = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        $secondRequest = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $firstRequest->refresh()->status);
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $secondRequest->refresh()->status);
        Event::assertDispatchedTimes(UpdateTopBarEvent::class, 1);
        Event::assertDispatchedTimes(UpdateCharacterCurrenciesEvent::class, 1);
        Event::assertDispatchedTimes(UpdateBaseCharacterInformation::class, 2);
    }

    public function test_secondary_updates_flush_before_a_processor_continuation_runs(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest();

        $npcQuestRewardHandler = Mockery::mock(NpcQuestRewardHandler::class);
        $npcQuestRewardHandler->shouldReceive('processReward')->times(51);
        $this->app->instance(NpcQuestRewardHandler::class, $npcQuestRewardHandler);

        for ($requestIndex = 0; $requestIndex < 51; $requestIndex++) {
            $this->createCharacterBattleRewardRequest([
                'character_id' => $character->id,
                'source_type' => BattleRewardRequestSourceType::QUEST,
                'status' => BattleRewardRequestStatus::PENDING,
                'source_id' => 'quest-continuation-'.$requestIndex,
                'handler_payload' => ['quest_id' => $quest->id],
            ]);
        }

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(
            51,
            CharacterBattleRewardRequest::where('character_id', $character->id)
                ->where('status', BattleRewardRequestStatus::COMPLETED)
                ->count(),
        );

        // One authoritative live Character update per completed request.
        Event::assertDispatchedTimes(UpdateBaseCharacterInformation::class, 51);

        // One coalesced secondary flush for the first 50-request slice (reaching
        // MAX_REQUESTS with pending work remaining) and one for the continuation
        // slice that processes the 51st request.
        Event::assertDispatchedTimes(UpdateTopBarEvent::class, 2);
        Event::assertDispatchedTimes(UpdateCharacterCurrenciesEvent::class, 2);
    }

    public function test_a_single_completed_request_dispatches_the_lightweight_live_update(): void
    {
        Event::fake();
        $this->createItem(['type' => 'weapon', 'skill_level_required' => 0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
        Event::assertDispatched(UpdateBaseCharacterInformation::class);
    }

    public function test_secondary_updates_flush_once_when_the_lane_drains_after_one_completed_request(): void
    {
        Event::fake();
        $this->createItem(['type' => 'weapon', 'skill_level_required' => 0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
        Event::assertDispatchedTimes(UpdateTopBarEvent::class, 1);
        Event::assertDispatchedTimes(UpdateCharacterCurrenciesEvent::class, 1);
    }

    public function test_message_outbox_retry_still_flushes_secondary_updates_without_replaying_the_reward(): void
    {
        // The test environment forces the `battle_reward_processing` connection to the
        // sync driver (see Tests\TestCase::setUp()), so the processor's own continuation
        // dispatch after a notification-retryable failure runs immediately, in-process,
        // as a second processor slice rather than a later queued attempt. The first slice
        // fails MESSAGE_OUTBOX and exits notification-retryable; the second slice resumes
        // directly into MESSAGE_OUTBOX (already-completed steps are skipped) and succeeds.
        Event::fake();
        $this->createItem(['type' => 'weapon', 'skill_level_required' => 0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $goldBefore = $character->gold;
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => []],
        ]);
        $messageOutboxService = Mockery::mock(BattleRewardMessageOutboxService::class)->makePartial();
        $messageOutboxService->shouldReceive('emitUnemittedMessages')->once()->andThrow(new RuntimeException('outbox boom'));
        $messageOutboxService->shouldReceive('emitUnemittedMessages')->andReturn(0);
        $this->app->instance(BattleRewardMessageOutboxService::class, $messageOutboxService);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $request = $request->refresh();
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->status);
        $this->assertSame(
            BattleRewardStepStatus::COMPLETED,
            $request->steps()->where('step_name', BattleRewardStepName::FINAL_PLAYER_UPDATES)->firstOrFail()->status,
        );
        $this->assertSame(
            BattleRewardStepStatus::COMPLETED,
            $request->steps()->where('step_name', BattleRewardStepName::MESSAGE_OUTBOX)->firstOrFail()->status,
        );
        $this->assertGreaterThan($goldBefore, $character->refresh()->gold);

        // One authoritative live update: FINAL_PLAYER_UPDATES only broadcasts it the first
        // time it actually completes; the resumed slice finds it already completed and skips.
        Event::assertDispatchedTimes(UpdateBaseCharacterInformation::class, 1);

        // One coalesced secondary flush per processor slice; two slices ran because the
        // first slice's MESSAGE_OUTBOX failure ended that slice notification-retryable.
        Event::assertDispatchedTimes(UpdateTopBarEvent::class, 2);
        Event::assertDispatchedTimes(UpdateCharacterCurrenciesEvent::class, 2);
    }

    public function test_quest_reward_reaches_the_lightweight_live_update_with_no_battle_only_steps(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createQuest([
            'reward_item' => null,
            'unlocks_skill' => false,
            'reward_gold' => 0,
            'reward_gold_dust' => 0,
            'reward_shards' => 0,
            'reward_xp' => 0,
        ]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['quest_id' => $quest->id],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $request = $request->refresh();
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->status);
        $this->assertSame(
            [BattleRewardStepName::FINAL_PLAYER_UPDATES, BattleRewardStepName::MESSAGE_OUTBOX],
            $request->steps()->orderBy('id')->pluck('step_name')->all(),
        );
        Event::assertDispatched(UpdateBaseCharacterInformation::class);
    }

    public function test_guide_quest_reward_reaches_the_lightweight_live_update_with_no_battle_only_steps(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $guideQuest = $this->createGuideQuest([
            'gold_reward' => 0,
            'gold_dust_reward' => 0,
            'shards_reward' => 0,
            'xp_reward' => 0,
        ]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::GUIDE_QUEST,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['guide_quest_id' => $guideQuest->id],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $request = $request->refresh();
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->status);
        $this->assertSame(
            [BattleRewardStepName::FINAL_PLAYER_UPDATES, BattleRewardStepName::MESSAGE_OUTBOX],
            $request->steps()->orderBy('id')->pluck('step_name')->all(),
        );
        Event::assertDispatched(UpdateBaseCharacterInformation::class);
    }

    public function test_faction_loyalty_reward_retains_seven_steps_and_reaches_the_lightweight_live_update(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::FACTION_LOYALTY,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => [
                'npc_name' => 'Test Npc',
                'game_map_name' => 'Surface',
                'reward_level' => 1,
                'new_fame_level' => 1,
                'max_level' => 5,
                'xp_amount' => 0,
                'gold_amount' => 0,
                'gold_dust_amount' => 0,
                'shards_amount' => 0,
            ],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $request = $request->refresh();
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->status);
        $this->assertSame(
            BattleRewardStepName::orderedForFactionLoyalty(),
            $request->steps()->orderBy('id')->pluck('step_name')->all(),
        );
        Event::assertDispatched(UpdateBaseCharacterInformation::class);
    }

    public function test_exploration_reward_emits_exploration_output_and_the_lightweight_live_update(): void
    {
        Event::fake();
        $this->createItem(['type' => 'weapon', 'skill_level_required' => 0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::EXPLORATION,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => ['total_creatures' => 1, 'total_xp' => 5]],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
        Event::assertDispatched(UpdateBaseCharacterInformation::class);
        Event::assertDispatched(ExplorationOutputUpdated::class);
    }

    public function test_delve_automation_reward_emits_delve_status_and_the_lightweight_live_update(): void
    {
        Event::fake();
        $this->createItem(['type' => 'weapon', 'skill_level_required' => 0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id, 'gold' => 10, 'xp' => 5]);
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::AUTOMATION,
            'status' => BattleRewardRequestStatus::PENDING,
            'handler_payload' => ['monster_id' => $monster->id, 'context' => ['total_creatures' => 1, 'total_xp' => 5]],
        ]);

        ProcessCharacterBattleRewardQueue::dispatch($character->id);

        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $request->refresh()->status);
        Event::assertDispatched(UpdateBaseCharacterInformation::class);
        Event::assertDispatched(DelveStatusUpdated::class);
    }
}
