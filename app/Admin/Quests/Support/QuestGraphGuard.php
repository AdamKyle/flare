<?php

namespace App\Admin\Quests\Support;

use App\Flare\Models\Quest;

class QuestGraphGuard
{
    /**
     * Resolve the parent-Quest-selection error, when the selection is invalid.
     */
    public function parentCycleError(?int $parentQuestId, ?int $excludeQuestId): ?string
    {
        if (is_null($parentQuestId)) {
            return null;
        }

        if ($parentQuestId === $excludeQuestId) {
            return 'A Quest cannot be its own parent.';
        }

        if ($this->walksInto($parentQuestId, 'parent_quest_id', $excludeQuestId)) {
            return 'The selected parent Quest would create a Quest chain cycle.';
        }

        return null;
    }

    /**
     * Resolve the required-Quest-selection error, when the selection is invalid.
     */
    public function requiredQuestCycleError(?int $requiredQuestId, ?int $excludeQuestId): ?string
    {
        if (is_null($requiredQuestId)) {
            return null;
        }

        if ($requiredQuestId === $excludeQuestId) {
            return 'A Quest cannot require itself.';
        }

        if ($this->walksInto($requiredQuestId, 'required_quest_id', $excludeQuestId)) {
            return 'The selected required Quest would create a required-Quest cycle.';
        }

        return null;
    }

    /**
     * Resolve the required-Quest-chain error, when the chain is invalid.
     */
    public function requiredQuestChainError(?array $chainIds, ?int $excludeQuestId): ?string
    {
        if (is_null($chainIds) || empty($chainIds)) {
            return null;
        }

        if (count($chainIds) !== count(array_unique($chainIds))) {
            return 'The required Quest chain contains duplicate Quests.';
        }

        if (! is_null($excludeQuestId) && in_array($excludeQuestId, $chainIds, true)) {
            return 'A Quest cannot require itself in its own required Quest chain.';
        }

        if (Quest::whereIn('id', $chainIds)->count() !== count($chainIds)) {
            return 'The required Quest chain references a Quest that no longer exists.';
        }

        if (! is_null($excludeQuestId)) {
            foreach ($chainIds as $chainId) {
                if ($this->chainTransitivelyRequires($chainId, $excludeQuestId, [])) {
                    return 'The required Quest chain would create a circular requirement.';
                }
            }
        }

        return null;
    }

    /**
     * Walk a single-column parent/requirement chain starting from an id, looking for a target id.
     */
    private function walksInto(int $startId, string $column, ?int $targetId): bool
    {
        if (is_null($targetId)) {
            return false;
        }

        $visited = [];
        $currentId = $startId;

        while (! is_null($currentId)) {
            if ($currentId === $targetId) {
                return true;
            }

            if (in_array($currentId, $visited, true)) {
                return false;
            }

            $visited[] = $currentId;
            $currentId = Quest::where('id', $currentId)->value($column);
        }

        return false;
    }

    /**
     * Walk a Quest's required-Quest and required-Quest-chain links, looking for a target id.
     */
    private function chainTransitivelyRequires(int $questId, int $targetId, array $visited): bool
    {
        if (in_array($questId, $visited, true)) {
            return false;
        }

        $visited[] = $questId;

        $quest = Quest::find($questId);

        if (is_null($quest)) {
            return false;
        }

        $linkedIds = array_filter(array_merge([$quest->required_quest_id], $quest->required_quest_chain ?? []), fn ($id) => ! is_null($id));

        foreach ($linkedIds as $linkedId) {
            if ($linkedId === $targetId || $this->chainTransitivelyRequires($linkedId, $targetId, $visited)) {
                return true;
            }
        }

        return false;
    }
}
