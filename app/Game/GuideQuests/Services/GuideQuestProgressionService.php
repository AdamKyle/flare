<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GuideQuest;
use App\Game\Events\Values\EventType;

class GuideQuestProgressionService
{
    /**
     * Return the Character's currently applicable Guide Quests.
     *
     * @param Character $character
     * @return array
     */
    public function currentQuests(Character $character): array
    {
        return array_values(array_filter([
            $this->fetchNextRegularGuideQuest($character),
            $this->fetchNextFeatureGuideQuest($character),
        ]));
    }

    /**
     * Resolve the Character's next level-unlocked or running-event Guide Quest.
     *
     * @param Character $character
     * @return GuideQuest|null
     */
    private function fetchNextFeatureGuideQuest(Character $character): ?GuideQuest
    {
        $activeEventTypes = $this->activeGuideQuestEventTypes();
        $unlocksAtLevelQuest = $this->unlocksAtLevelGuideQuest($character, $activeEventTypes);
        $nextGuideQuest = is_null($unlocksAtLevelQuest)
            ? null
            : $this->fetchNextEventQuest($character, $unlocksAtLevelQuest);

        if (! is_null($nextGuideQuest)) {
            return $nextGuideQuest;
        }

        foreach ($activeEventTypes as $eventType) {
            $eventGuideQuest = $this->fetchNextEventRootQuest($character, $eventType);

            if (! is_null($eventGuideQuest)) {
                return $eventGuideQuest;
            }
        }

        return null;
    }

    /**
     * Return running events that own Guide Quest chains in precedence order.
     *
     * @return array
     */
    private function activeGuideQuestEventTypes(): array
    {
        return array_values(array_filter(
            [EventType::WINTER_EVENT, EventType::DELUSIONAL_MEMORIES_EVENT],
            fn (int $eventType): bool => Event::where('type', $eventType)->exists(),
        ));
    }

    /**
     * Resolve the level-unlocked Guide Quest for the current event state.
     *
     * @param Character $character
     * @param array $activeEventTypes
     * @return GuideQuest|null
     */
    private function unlocksAtLevelGuideQuest(Character $character, array $activeEventTypes): ?GuideQuest
    {
        if (empty($activeEventTypes)) {
            return GuideQuest::where('unlock_at_level', '<=', $character->level)
                ->whereNull('only_during_event')
                ->whereNull('parent_id')
                ->orderBy('unlock_at_level', 'asc')
                ->first();
        }

        return GuideQuest::where('unlock_at_level', '>=', $character->level)
            ->where('only_during_event', $activeEventTypes[array_key_last($activeEventTypes)])
            ->whereNull('parent_id')
            ->orderBy('unlock_at_level', 'asc')
            ->first();
    }

    /**
     * Resolve the next incomplete quest from an event root.
     *
     * @param Character $character
     * @param int $eventType
     * @return GuideQuest|null
     */
    private function fetchNextEventRootQuest(Character $character, int $eventType): ?GuideQuest
    {
        $eventGuideQuest = GuideQuest::where('only_during_event', $eventType)
            ->whereNull('parent_id')
            ->first();

        if (is_null($eventGuideQuest)) {
            return null;
        }

        return $this->fetchNextEventQuest($character, $eventGuideQuest);
    }

    /**
     * Resolve the Character's next incomplete regular Guide Quest.
     *
     * @param Character $character
     * @return GuideQuest|null
     */
    private function fetchNextRegularGuideQuest(Character $character): ?GuideQuest
    {
        $completedGuideQuestIds = $character->questsCompleted()
            ->whereNotNull('guide_quest_id')
            ->pluck('guide_quest_id')
            ->all();
        $roots = GuideQuest::whereNull('only_during_event')
            ->whereNull('unlock_at_level')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        foreach ($roots as $root) {
            $nextIncompleteQuest = $this->findNextIncompleteRegularGuideQuest($root, $completedGuideQuestIds);

            if (! is_null($nextIncompleteQuest)) {
                return $nextIncompleteQuest;
            }
        }

        return null;
    }

    /**
     * Find the first incomplete regular Guide Quest depth-first.
     *
     * @param GuideQuest $quest
     * @param array $completedGuideQuestIds
     * @return GuideQuest|null
     */
    private function findNextIncompleteRegularGuideQuest(GuideQuest $quest, array $completedGuideQuestIds): ?GuideQuest
    {
        if (! in_array($quest->id, $completedGuideQuestIds, true)) {
            return $quest;
        }

        $children = GuideQuest::whereNull('only_during_event')
            ->whereNull('unlock_at_level')
            ->where('parent_id', $quest->id)
            ->orderBy('id')
            ->get();

        foreach ($children as $child) {
            $nextIncompleteQuest = $this->findNextIncompleteRegularGuideQuest($child, $completedGuideQuestIds);

            if (! is_null($nextIncompleteQuest)) {
                return $nextIncompleteQuest;
            }
        }

        return null;
    }

    /**
     * Resolve the Character's next incomplete event-chain quest.
     *
     * @param Character $character
     * @param GuideQuest $initialEventGuideQuest
     * @return GuideQuest|null
     */
    private function fetchNextEventQuest(Character $character, GuideQuest $initialEventGuideQuest): ?GuideQuest
    {
        $completedFirstEventQuest = $character->questsCompleted()
            ->where('guide_quest_id', $initialEventGuideQuest->id)
            ->first();

        if (is_null($completedFirstEventQuest)) {
            return $initialEventGuideQuest;
        }

        $nextGuideQuest = GuideQuest::where('parent_id', $initialEventGuideQuest->id)
            ->orderBy('id')
            ->first();

        if (is_null($nextGuideQuest)) {
            return null;
        }

        return $this->fetchNextEventQuest($character, $nextGuideQuest);
    }
}
