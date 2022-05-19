<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;

class GuideQuestService {

    public function __construct() {

    }

    public function fetchQuestForCharacter(Character $character): GuideQuest | null {
        $lastCompletedGuideQuest = $character->questsCompleted()->orderByDesc('guide_quest_id')->first();

        if (is_null($lastCompletedGuideQuest)) {
            $quest = GuideQuest::first();
        } else {
            $quest = GuideQuest::find($lastCompletedGuideQuest->id + 1);
        }

        if (is_null($quest)) {
            return null;
        }

        return $quest;
    }

    public function canHandInQuest(Character $character, GuideQuest $quest): bool {
        if (!is_null($quest->required_level)) {
            return $character->level === $quest->required_level;
        }

        return false;
    }
}
