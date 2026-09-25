<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardPresentationQueueManager;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;

class BattleRewardPresentationQueueManagerTest extends TestCase
{
    use CreateCharacterBattleReward, RefreshDatabase;

    public function test_next_request_returns_the_oldest_completed_request_with_unfinished_presentation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $olderRequest = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $olderRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
            'status' => BattleRewardStepStatus::PENDING,
        ]);
        $newerRequest = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $newerRequest->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::FINAL_PLAYER_UPDATES,
            'status' => BattleRewardStepStatus::PENDING,
        ]);

        $nextRequest = resolve(BattleRewardPresentationQueueManager::class)->nextRequest($character->id);

        $this->assertSame($olderRequest->id, $nextRequest->id);
    }

    public function test_fully_presented_completed_requests_are_not_pending_presentation(): void
    {
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
            'status' => BattleRewardStepStatus::COMPLETED,
        ]);

        $queueManager = resolve(BattleRewardPresentationQueueManager::class);

        $this->assertNull($queueManager->nextRequest($character->id));
        $this->assertFalse($queueManager->hasPendingRequests($character->id));
    }

    public function test_requests_still_awaiting_reward_mutation_are_not_pending_presentation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $request = $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::PENDING,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $character->id,
            'step_name' => BattleRewardStepName::MESSAGE_OUTBOX,
            'status' => BattleRewardStepStatus::PENDING,
        ]);

        $queueManager = resolve(BattleRewardPresentationQueueManager::class);

        $this->assertNull($queueManager->nextRequest($character->id));
        $this->assertFalse($queueManager->hasPendingRequests($character->id));
    }

    public function test_presentation_lock_is_independent_of_the_reward_mutation_lock(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $mutationLock = resolve(BattleRewardProcessingQueueManager::class)->processorLock($character->id);
        $mutationLock->get();

        $presentationLock = resolve(BattleRewardPresentationQueueManager::class)->processorLock($character->id);

        $this->assertTrue($presentationLock->get());

        $presentationLock->release();
        $mutationLock->release();
    }
}
