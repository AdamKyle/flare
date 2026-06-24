<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Jobs\ProcessCharacterBattleRewardQueue;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Quests\Handlers\NpcQuestRewardHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ProcessCharacterBattleRewardQueueTest extends TestCase
{
    use MockeryPHPUnitIntegration, RefreshDatabase;

    public function testProcessorDrainsFirstPriorityBeforeSecondAndMarksQueueInactive(): void
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
        $battleRewardService->shouldReceive('setUp')->once()->with($character->id, 22)->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->once()->with([])->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->once()->with(true);

        $job = new ProcessCharacterBattleRewardQueue($character->id);
        $job->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::FAILED, $first->refresh()->status);
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $second->refresh()->status);
        $this->assertFalse(CharacterBattleRewardQueueState::where('character_id', $character->id)->firstOrFail()->is_processing);
    }

    public function testProcessorContinuesAfterFailure(): void
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
        $battleRewardService->shouldReceive('setUp')->with($character->id, 1)->once()->andThrow(new RuntimeException('reward failed'));
        $battleRewardService->shouldReceive('setUp')->with($character->id, 2)->once()->andReturnSelf();
        $battleRewardService->shouldReceive('setContext')->with([])->once()->andReturnSelf();
        $battleRewardService->shouldReceive('processRewards')->with(true)->once();

        (new ProcessCharacterBattleRewardQueue($character->id))->handle(
            resolve(BattleRewardProcessingQueueManager::class),
            $battleRewardService,
            Mockery::mock(NpcQuestRewardHandler::class),
            Mockery::mock(GuideQuestService::class),
        );

        $this->assertSame(BattleRewardRequestStatus::FAILED, $failed->refresh()->status);
        $this->assertStringContainsString('reward failed', $failed->failed_reason);
        $this->assertSame(BattleRewardRequestStatus::COMPLETED, $completed->refresh()->status);
    }

    public function testProcessorDrainsRequestInsertedWhileProcessing(): void
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
        );

        $this->assertSame(
            2,
            CharacterBattleRewardRequest::forCharacter($character->id)->completed()->count(),
        );
    }
}
