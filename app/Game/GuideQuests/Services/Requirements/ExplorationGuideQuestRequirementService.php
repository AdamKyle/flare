<?php

namespace App\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\GuideQuest;

class ExplorationGuideQuestRequirementService
{
    /**
     * Return completed Exploration and Delve requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $quest): array
    {
        return array_values(array_filter([
            $this->delveSurvivalTime($character, $quest),
            $this->explorationHours($character, $quest),
            $this->delvePackSize($character, $quest),
        ]));
    }

    /**
     * Return the completed Delve survival requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function delveSurvivalTime(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_delve_survival_time)) {
            return null;
        }

        $delve = $this->latestCompletedDelve($character);

        if (is_null($delve)) {
            return null;
        }

        return $delve->started_at->diffInHours($delve->completed_at) >= $quest->required_delve_survival_time
            ? 'required_delve_survival_time'
            : null;
    }

    /**
     * Return the completed Exploration duration requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function explorationHours(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_exploration_hours)) {
            return null;
        }

        $completed = ExplorationLog::where('character_id', $character->id)
            ->whereNotNull('ended_at')
            ->get()
            ->contains(fn (ExplorationLog $log): bool => $log->started_at->diffInMinutes($log->ended_at) >= $quest->required_exploration_hours * 60);

        return $completed ? 'required_exploration_hours' : null;
    }

    /**
     * Return the completed latest Delve pack-size requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function delvePackSize(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_delve_pack_size)) {
            return null;
        }

        $delve = $this->latestCompletedDelve($character);

        if (is_null($delve)) {
            return null;
        }

        $packSize = DelveLog::where('character_id', $character->id)
            ->where('delve_exploration_id', $delve->id)
            ->orderBy('created_at', 'desc')
            ->value('pack_size');

        return ! is_null($packSize) && $packSize >= $quest->required_delve_pack_size
            ? 'required_delve_pack_size'
            : null;
    }

    /**
     * Return the Character's latest completed Delve Exploration.
     *
     * @param Character $character
     * @return DelveExploration|null
     */
    private function latestCompletedDelve(Character $character): ?DelveExploration
    {
        return DelveExploration::where('character_id', $character->id)
            ->whereNotNull('completed_at')
            ->orderBy('started_at', 'desc')
            ->first();
    }
}
