<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\QuestsCompleted;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use App\Game\BattleRewardProcessing\Values\BattleRewardEnqueueResult;
use App\Game\GuideQuests\Services\GuideQuestReadinessService;
use App\Game\GuideQuests\Services\GuideQuestRewardService;
use App\Game\GuideQuests\Services\Requirements\CraftingGuideQuestRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\GuideQuest\GuideQuestServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;

class GuideQuestRewardServiceTest extends TestCase
{
    use CreateGuideQuest, RefreshDatabase;

    public function test_successful_hand_in_creates_one_completion_and_queues_one_reward(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createGuideQuest(['required_level' => 1]);
        $craftingRequirements = new CraftingGuideQuestRequirementService;
        $requirements = (new GuideQuestServiceFactory)->buildRequirements($craftingRequirements);
        $service = new GuideQuestRewardService(
            new GuideQuestReadinessService($requirements),
            $craftingRequirements,
            new BattleRewardProcessingQueueManager,
        );

        $firstHandIn = $service->handIn($character, $quest);
        $secondHandIn = $service->handIn($character->refresh(), $quest->refresh());

        $this->assertTrue($firstHandIn);
        $this->assertFalse($secondHandIn);
        $this->assertSame(1, QuestsCompleted::where('character_id', $character->id)->where('guide_quest_id', $quest->id)->count());
        $this->assertSame(1, CharacterBattleRewardRequest::where('character_id', $character->id)->count());
    }

    public function test_failed_reward_queue_insertion_removes_the_completion(): void
    {
        Event::fake();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createGuideQuest(['required_level' => 1]);
        $craftingRequirements = new CraftingGuideQuestRequirementService;
        $requirements = (new GuideQuestServiceFactory)->buildRequirements($craftingRequirements);
        $queueManager = Mockery::mock(BattleRewardProcessingQueueManager::class);
        $queueManager->shouldReceive('enqueue')
            ->once()
            ->andReturn(BattleRewardEnqueueResult::failed(new RuntimeException('database unavailable')));
        $service = new GuideQuestRewardService(
            new GuideQuestReadinessService($requirements),
            $craftingRequirements,
            $queueManager,
        );

        $handedIn = $service->handIn($character, $quest);

        $this->assertFalse($handedIn);
        $this->assertSame(0, QuestsCompleted::where('character_id', $character->id)->where('guide_quest_id', $quest->id)->count());
    }
}
