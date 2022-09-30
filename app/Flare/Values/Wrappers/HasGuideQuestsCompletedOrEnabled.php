<?php

namespace App\Flare\Values\Wrappers;

use App\Flare\Models\QuestsCompleted;

class HasGuideQuestsCompletedOrEnabled {

    /**
     * Allows us to show the sidebar option if they have completed quests or it's enabled.
     *
     * @return bool
     */
    public static function canSeeGuideQuestsLog(): bool {
        $user = auth()->user();

        $hasCompletedGuideQuests = QuestsCompleted::where('character_id', $user->character->id)
                                                  ->whereNotNull('guide_quest_id')
                                                  ->count() > 0;

        $hasGuideQuestsEnabled   = $user->guide_enabled;

        return $hasCompletedGuideQuests || $hasGuideQuestsEnabled;
    }
}
