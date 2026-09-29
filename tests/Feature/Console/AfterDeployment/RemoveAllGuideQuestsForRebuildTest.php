<?php

namespace Tests\Feature\Console\AfterDeployment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateGuideQuestWalkthrough;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateQuestsCompleted;

class RemoveAllGuideQuestsForRebuildTest extends TestCase
{
    use CreateGuideQuest;
    use CreateGuideQuestWalkthrough;
    use CreateQuest;
    use CreateQuestsCompleted;
    use RefreshDatabase;

    public function test_command_removes_only_guide_quest_state(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $guideQuest = $this->createGuideQuest();
        $quest = $this->createQuest();
        $step = $this->createGuideQuestWalkthroughStep([
            'guide_quest_id' => $guideQuest->id,
        ]);

        $this->createQuestsCompleted([
            'character_id' => $character->id,
            'guide_quest_id' => $guideQuest->id,
        ]);
        $ordinaryCompletion = $this->createQuestsCompleted([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);
        $this->createCharacterGuideQuestProgress([
            'character_id' => $character->id,
            'guide_quest_id' => $guideQuest->id,
            'current_guide_quest_walkthrough_step_id' => $step->id,
        ]);

        $this->artisan('guide-quest-rebuild:remove-all-guide-quests')
            ->expectsOutput('Character Guide Quest walkthrough progress deleted: 1')
            ->expectsOutput('Guide Quest walkthrough steps deleted: 1')
            ->expectsOutput('Guide Quest completion rows deleted: 1')
            ->expectsOutput('Guide Quests deleted: 1')
            ->expectsOutput('Guide Quest rebuild cleanup complete.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('guide_quests', ['id' => $guideQuest->id]);
        $this->assertDatabaseMissing('quests_completed', ['guide_quest_id' => $guideQuest->id]);
        $this->assertDatabaseHas('quests_completed', ['id' => $ordinaryCompletion->id]);
        $this->assertDatabaseCount('guide_quest_walkthrough_steps', 0);
        $this->assertDatabaseCount('character_guide_quest_progress', 0);
    }
}
