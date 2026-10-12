<?php

namespace App\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;

class CharacterGuideQuestRequirementService
{
    private const STATS = ['str', 'dex', 'dur', 'int', 'chr', 'agi', 'focus'];

    private const CURRENCIES = ['gold', 'gold_dust', 'shards', 'copper_coins'];

    /**
     * Return completed Character requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $quest): array
    {
        $completedRequirements = array_filter([
            $this->level($character, $quest),
            $this->reincarnations($character, $quest),
            $this->fameLevel($character, $quest),
            $this->specialtyType($character, $quest),
            $this->holyStacks($character, $quest),
            $this->classRanksEquipped($character, $quest),
            $this->classRankLevel($character, $quest),
            $this->totalStats($character, $quest),
        ]);
        $completedRequirements = array_merge(
            $completedRequirements,
            $this->currencies($character, $quest),
            $this->individualStats($character, $quest),
        );

        return array_values(array_unique($completedRequirements));
    }

    /**
     * Return the completed Character level requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function level(Character $character, GuideQuest $quest): ?string
    {
        return ! is_null($quest->required_level) && $character->level >= $quest->required_level
            ? 'required_level'
            : null;
    }

    /**
     * Return the completed reincarnation requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function reincarnations(Character $character, GuideQuest $quest): ?string
    {
        return ! is_null($quest->required_reincarnation_amount)
            && $character->times_reincarnated >= $quest->required_reincarnation_amount
                ? 'required_reincarnation_amount'
                : null;
    }

    /**
     * Return the completed Faction Loyalty fame requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function fameLevel(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_fame_level)) {
            return null;
        }

        $faction = $character->factionLoyalties()->where('is_pledged', true)->first();

        if (is_null($faction)) {
            return null;
        }

        $npc = $faction->factionLoyaltyNpcs()->where('currently_helping', true)->first();

        return ! is_null($npc) && $npc->current_level >= $quest->required_fame_level
            ? 'required_fame_level'
            : null;
    }

    /**
     * Return the completed Item specialty requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function specialtyType(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_specialty_type)) {
            return null;
        }

        $inInventory = $character->inventory->slots->contains(
            fn ($slot): bool => $slot->item->specialty_type === $quest->required_specialty_type,
        );
        $inSet = $character->inventorySets->contains(
            fn ($set): bool => $set->slots->contains(
                fn ($slot): bool => $slot->item->specialty_type === $quest->required_specialty_type,
            ),
        );

        return $inInventory || $inSet ? 'required_specialty_type' : null;
    }

    /**
     * Return the completed Holy Stack requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function holyStacks(Character $character, GuideQuest $quest): ?string
    {
        return ! is_null($quest->required_holy_stacks)
            && $character->getInformation()->holyInfo()->getTotalAppliedStacks() >= $quest->required_holy_stacks
                ? 'required_holy_stacks'
                : null;
    }

    /**
     * Return the completed equipped Class Special requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function classRanksEquipped(Character $character, GuideQuest $quest): ?string
    {
        return ! is_null($quest->required_class_specials_equipped)
            && $character->classSpecialsEquipped()->count() >= $quest->required_class_specials_equipped
                ? 'required_class_specials_equipped'
                : null;
    }

    /**
     * Return the completed Class Rank level requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function classRankLevel(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_class_rank_level)) {
            return null;
        }

        return $character->classRanks->where('level', '>=', $quest->required_class_rank_level)->isNotEmpty()
            ? 'required_class_rank_level'
            : null;
    }

    /**
     * Return completed currency requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    private function currencies(Character $character, GuideQuest $quest): array
    {
        $completed = [];

        foreach (self::CURRENCIES as $currency) {
            $attribute = 'required_'.$currency;

            if (! is_null($quest->{$attribute}) && $character->{$currency} >= $quest->{$attribute}) {
                $completed[] = $attribute;
            }
        }

        return $completed;
    }

    /**
     * Return the completed total-stat requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function totalStats(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_stats)) {
            return null;
        }

        $complete = collect(self::STATS)->every(
            fn (string $stat): bool => $character->getInformation()->statMod($stat) >= $quest->required_stats,
        );

        return $complete ? 'required_stats' : null;
    }

    /**
     * Return completed individual-stat requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    private function individualStats(Character $character, GuideQuest $quest): array
    {
        $completed = [];

        foreach (self::STATS as $stat) {
            $attribute = 'required_'.$stat;

            if (! is_null($quest->{$attribute}) && $character->getInformation()->statMod($stat) >= $quest->{$attribute}) {
                $completed[] = $attribute;
            }
        }

        return $completed;
    }
}
