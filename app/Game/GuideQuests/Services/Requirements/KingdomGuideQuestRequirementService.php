<?php

namespace App\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GuideQuest;

class KingdomGuideQuestRequirementService
{
    /**
     * Return completed Kingdom requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $quest): array
    {
        return array_values(array_filter([
            $this->kingdomCount($character, $quest),
            $this->buildingLevel($character, $quest),
            $this->goldBars($character, $quest),
            $this->specificBuildingLevel($character, $quest),
            $this->unitCount($character, $quest),
            $this->passiveLevel($character, $quest),
        ]));
    }

    /**
     * Return the completed Kingdom count requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function kingdomCount(Character $character, GuideQuest $quest): ?string
    {
        return ! is_null($quest->required_kingdoms) && $character->kingdoms->count() >= $quest->required_kingdoms
            ? 'required_kingdoms'
            : null;
    }

    /**
     * Return the completed combined building-level requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function buildingLevel(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_kingdom_level)) {
            return null;
        }

        $complete = $character->kingdoms->contains(
            fn ($kingdom): bool => $kingdom->buildings->sum('level') >= $quest->required_kingdom_level,
        );

        return $complete ? 'required_kingdom_level' : null;
    }

    /**
     * Return the completed Kingdom Gold Bars requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function goldBars(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_gold_bars)) {
            return null;
        }

        $complete = $character->kingdoms->contains(
            fn ($kingdom): bool => $kingdom->sum('gold_bars') >= $quest->required_gold_bars,
        );

        return $complete ? 'required_gold_bars' : null;
    }

    /**
     * Return the completed specific building-level requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function specificBuildingLevel(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_kingdom_building_level) || is_null($quest->required_kingdom_building_id)) {
            return null;
        }

        $building = GameBuilding::find($quest->required_kingdom_building_id);

        if (is_null($building)) {
            return null;
        }

        $count = $character->kingdoms()->whereHas('buildings', function ($query) use ($building, $quest) {
            $query->where('game_building_id', $building->id)
                ->where('level', '>=', $quest->required_kingdom_building_level);
        })->count();

        return $count > 0 ? 'required_kingdom_building_level' : null;
    }

    /**
     * Return the completed Kingdom unit-count requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function unitCount(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_kingdom_units)) {
            return null;
        }

        $totalUnits = $character->kingdoms->sum(fn ($kingdom) => $kingdom->units->sum('amount'));

        return $totalUnits >= $quest->required_kingdom_units ? 'required_kingdom_units' : null;
    }

    /**
     * Return the completed Kingdom passive requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function passiveLevel(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_passive_skill) || is_null($quest->required_passive_level)) {
            return null;
        }

        $skill = $character->passiveSkills()->where('passive_skill_id', $quest->required_passive_skill)->first();

        return ! is_null($skill) && $skill->current_level >= $quest->required_passive_level
            ? 'required_passive_level'
            : null;
    }
}
