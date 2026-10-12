<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Game\GuideQuests\Services\GuideQuestReadinessService;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateQuestsCompleted;

class GuideQuestReadinessServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateCharacterAutomation, CreateGuideQuest, CreateQuestsCompleted, RefreshDatabase;

    public function test_completed_quest_cannot_be_handed_in(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createGuideQuest(['required_level' => 1]);
        $this->createQuestsCompleted([
            'character_id' => $character->id,
            'guide_quest_id' => $quest->id,
        ]);

        $canHandIn = (new GuideQuestReadinessService(new GuideQuestRequirementsService))
            ->canHandIn($character, $quest);

        $this->assertFalse($canHandIn);
    }

    public function test_exploration_blocks_normal_hand_in_but_can_be_ignored_for_attention(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createGuideQuest(['required_level' => 1]);
        $this->createCharacterAutomation(['character_id' => $character->id]);
        $service = new GuideQuestReadinessService(new GuideQuestRequirementsService);

        $this->assertFalse($service->canHandIn($character, $quest));
        $this->assertTrue($service->canHandIn($character, $quest, true));
    }

    public function test_active_batch_crafting_blocks_a_batch_item_requirement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => 1, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $canHandIn = (new GuideQuestReadinessService(new GuideQuestRequirementsService))
            ->canHandIn($character, $quest);

        $this->assertFalse($canHandIn);
    }

    public function test_all_requirements_complete_returns_true_and_missing_requirement_returns_false(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $completedQuest = $this->createGuideQuest(['required_level' => 1]);
        $incompleteQuest = $this->createGuideQuest(['required_level' => 2]);
        $service = new GuideQuestReadinessService(new GuideQuestRequirementsService);

        $this->assertTrue($service->canHandIn($character, $completedQuest));
        $this->assertFalse($service->canHandIn($character, $incompleteQuest));
    }
}
