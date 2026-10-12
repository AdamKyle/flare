<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGuideQuestProgress;
use App\Flare\Models\GuideQuest;

class GuideQuestAttentionService
{
    /**
     * Record the first transition into a hand-in-ready state.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @param bool $canHandIn
     * @return void
     */
    public function recordReadyState(Character $character, GuideQuest $guideQuest, bool $canHandIn): void
    {
        if (! $canHandIn) {
            return;
        }

        CharacterGuideQuestProgress::firstOrCreate(
            ['character_id' => $character->id, 'guide_quest_id' => $guideQuest->id],
            ['hand_in_ready_at' => now()],
        );

        CharacterGuideQuestProgress::where('character_id', $character->id)
            ->where('guide_quest_id', $guideQuest->id)
            ->whereNull('hand_in_ready_at')
            ->update(['hand_in_ready_at' => now()]);
    }

    /**
     * Determine whether current Guide Quests have unacknowledged attention.
     *
     * @param Character $character
     * @param array $quests
     * @return bool
     */
    public function hasUnacknowledgedAttention(Character $character, array $quests): bool
    {
        return CharacterGuideQuestProgress::where('character_id', $character->id)
            ->whereIn('guide_quest_id', collect($quests)->pluck('id'))
            ->whereNotNull('hand_in_ready_at')
            ->whereNull('attention_acknowledged_at')
            ->exists();
    }

    /**
     * Acknowledge attention for the supplied Guide Quests.
     *
     * @param Character $character
     * @param array $quests
     * @return void
     */
    public function acknowledge(Character $character, array $quests): void
    {
        CharacterGuideQuestProgress::where('character_id', $character->id)
            ->whereIn('guide_quest_id', collect($quests)->pluck('id'))
            ->whereNotNull('hand_in_ready_at')
            ->whereNull('attention_acknowledged_at')
            ->update(['attention_acknowledged_at' => now()]);
    }
}
