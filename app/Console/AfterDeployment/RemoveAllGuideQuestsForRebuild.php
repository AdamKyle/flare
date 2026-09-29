<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\CharacterGuideQuestProgress;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\GuideQuestWalkthroughStep;
use App\Flare\Models\QuestsCompleted;
use Illuminate\Console\Command;

class RemoveAllGuideQuestsForRebuild extends Command
{
    protected $signature = 'guide-quest-rebuild:remove-all-guide-quests';

    protected $description = 'Deletes Guide Quests, walkthrough state and Guide Quest completions while preserving ordinary Quest completions.';

    /**
     * Remove Guide Quest state in foreign-key-safe order and report each count.
     *
     * @return int
     */
    public function handle(): int
    {
        $progressDeleted = CharacterGuideQuestProgress::query()->delete();
        $stepsDeleted = GuideQuestWalkthroughStep::query()->delete();
        $completionsDeleted = QuestsCompleted::whereNotNull('guide_quest_id')->delete();
        $guideQuestsDeleted = GuideQuest::query()->delete();

        $this->line('Character Guide Quest walkthrough progress deleted: '.$progressDeleted);
        $this->line('Guide Quest walkthrough steps deleted: '.$stepsDeleted);
        $this->line('Guide Quest completion rows deleted: '.$completionsDeleted);
        $this->line('Guide Quests deleted: '.$guideQuestsDeleted);
        $this->line('Guide Quest rebuild cleanup complete.');

        return self::SUCCESS;
    }
}
