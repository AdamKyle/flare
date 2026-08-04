<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\QuestsCompleted;

class GuideQuestVisibilityService
{
    /**
     * Allows us to show the sidebar option if they have completed quests or it's enabled.
     */
    public static function canSeeGuideQuestsLog(): bool
    {
        $user = auth()->user();

        $hasCompletedGuideQuests = QuestsCompleted::where('character_id', $user->character->id)
            ->whereNotNull('guide_quest_id')
            ->count() > 0;

        $hasGuideQuestsEnabled = $user->guide_enabled;

        return $hasCompletedGuideQuests || $hasGuideQuestsEnabled;
    }
}
