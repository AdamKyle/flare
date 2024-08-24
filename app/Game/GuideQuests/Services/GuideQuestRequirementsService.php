<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GuideQuest;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Illuminate\Support\Facades\Log;

class GuideQuestRequirementsService
{
    private array $finishedRequirements = [];

    /**
     * Get the finished requirements.
     */
    public function getFinishedRequirements(): array
    {
        return $this->finishedRequirements;
    }

    /**
     * Reset the finished requirements.
     */
    public function resetFinishedRequirements(): void
    {
        $this->finishedRequirements = [];
    }

    /**
     * Does character meet the required level?
     */
    public function requiredLevelCheck(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_level)) {
            if ($character->level >= $quest->required_level) {
                $this->finishedRequirements[] = 'required_level';
            }
        }

        return $this;
    }

    /**
     * Does character have the required skill  attributes?
     */
    public function requiredSkillCheck(Character $character, GuideQuest $quest, bool $primary = true): GuideQuestRequirementsService
    {
        $attribute = $primary ? 'required_skill' : 'required_secondary_skill';

        if (! is_null($quest->{$attribute})) {
            $requiredSkill = $character->skills()->where('game_skill_id', $quest->{$attribute})->first();

            $attribute = $primary ? 'required_skill_level' : 'required_secondary_skill_level';

            if ($requiredSkill->level >= $quest->{$attribute}) {
                $this->finishedRequirements[] = $attribute;
            }
        }

        return $this;
    }

    /**
     * Does character have the required skill type?
     */
    public function requiredSkillTypeCheck(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_skill_type)) {
            try {
                $skillType = new SkillTypeValue($quest->required_skill_type);

                if ($skillType->effectsClassSkills()) {
                    $this->classSkillCheck($character, $quest);
                }

                if ($skillType->isCrafting()) {
                    $this->craftingSkillTypeCheck($character, $quest);
                }
            } catch (Exception $e) {
                Log::info($e->getmessage());
            }
        }

        return $this;
    }

    /**
     * Has the character gained the right faction amount?
     */
    public function requiredFactionLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_faction_id)) {
            $faction = $character->factions()->where('game_map_id', $quest->required_faction_id)->first();

            if ($faction->current_level >= $quest->required_faction_level) {
                $this->finishedRequirements[] = 'required_faction_level';
            }
        }

        return $this;
    }

    /**
     * Does character have access to a specific map?
     */
    public function requiredGameMapAccess(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_game_map_id)) {
            $gameMap = GameMap::find($quest->required_game_map_id);

            $canHandIn = $character->inventory->slots->filter(function ($slot) use ($gameMap) {
                return $slot->item->type === 'quest' && $slot->item->id === $gameMap->map_required_item->id;
            })->isNotEmpty();

            if ($canHandIn) {
                $this->finishedRequirements[] = 'required_game_map_id';
            }
        }

        return $this;
    }

    /**
     * Does character have a specific quest completed?
     */
    public function requiredQuest(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_quest_id)) {
            $canHandIn = ! is_null($character->questsCompleted()->where('quest_id', $quest->required_quest_id)->first());

            if ($canHandIn) {
                $this->finishedRequirements[] = 'required_quest_id';
            }
        }

        return $this;
    }

    /**
     * Does character have a specific quest item?
     */
    public function requiredQuestItem(Character $character, GuideQuest $quest, bool $primary = true): GuideQuestRequirementsService
    {
        $attribute = $primary ? 'required_quest_item_id' : 'secondary_quest_item_id';

        if (! is_null($quest->{$attribute})) {
            $canHandIn = $character->inventory->slots->filter(function ($slot) use ($quest, $attribute) {
                return $slot->item->type === 'quest' && $slot->item->id === $quest->{$attribute};
            })->isNotEmpty();

            if ($canHandIn || $this->wasItemUsedInQuest($character, $quest->{$attribute})) {
                $this->finishedRequirements[] = $attribute;
            }
        }

        return $this;
    }

    /**
     * Does the players current fame level match that of the requirement?
     *
     * - Players must be pledged
     * - Players must be assisting
     * - Players must have their fame level with that NPC at or higher than the requirement.
     *
     * @return $this
     */
    public function requiredFameLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {

        if (! is_null($quest->required_fame_level)) {

            $pledgedFaction = $character->factionLoyalties()->where('is_pledged', true)->first();

            if (is_null($pledgedFaction)) {
                return $this;
            }

            $currentlyHelpingNpc = $pledgedFaction->factionLoyaltyNpcs()->where('currently_helping', true)->first();

            if (is_null($currentlyHelpingNpc)) {
                return $this;
            }

            if ($currentlyHelpingNpc->current_level >= $quest->required_fame_level) {
                $this->finishedRequirements[] = 'required_fame_level';
            }
        }

        return $this;
    }

    /**
     * Do we have an item that has a specific specialty type either in out inventory or in a set?
     *
     * @return $this
     */
    public function requiredSpecialtyType(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_specialty_type)) {
            $isInInventory = $character->inventory->slots->filter(function ($slot) use ($quest) {
                return $slot->item->specialty_type === $quest->required_specialty_type;
            })->isNotEmpty();

            $isInSet = $character->inventorySets->filter(function ($set) use ($quest) {
                return $set->slots->filter(function ($slot) use ($quest) {
                    $slot->item->specialty_type === $quest->required_specialty_type;
                })->isNotempty();
            })->isNotEmpty();

            if ($isInInventory || $isInSet) {
                $this->finishedRequirements[] = 'required_specialty_type';
            }
        }

        return $this;
    }

    /**
     * Does the character have the required holy stacks?
     *
     * @return $this
     */
    public function requiredHolyStacks(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {

        if (is_null($quest->required_holy_stacks)) {
            return $this;
        }

        if ($character->getInformation()->holyInfo()->getTotalAppliedStacks() >= $quest->required_holy_stacks) {
            $this->finishedRequirements[] = 'required_holy_stacks';
        }

        return $this;
    }

    /**
     * Does character have the required kingdom count?
     */
    public function requiredKingdomCount(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_kingdoms)) {
            if ($character->kingdoms->count() >= $quest->required_kingdoms) {
                $this->finishedRequirements[] = 'required_kingdoms';
            }
        }

        return $this;
    }

    /**
     * Does the combined levels of all buildings across all kingdoms meet or exceed the required?
     */
    public function requiredKingdomBuildingLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_kingdom_level)) {
            foreach ($character->kingdoms as $kingdom) {
                if ($kingdom->buildings->sum('level') >= $quest->required_kingdom_level) {
                    $this->finishedRequirements[] = 'required_kingdom_level';

                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Does the combined gold bars across all kingdoms match or exceed the amount required?
     */
    public function requiredKingdomGoldBarsAmount(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_gold_bars)) {
            foreach ($character->kingdoms as $kingdom) {
                if ($kingdom->sum('gold_bars') >= $quest->required_gold_bars) {
                    $this->finishedRequirements[] = 'required_gold_bars';

                    break;
                }
            }
        }

        return $this;
    }

    public function requiredKingdomSpecificBuildingLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_kingdom_building_level) && ! is_null($quest->required_kingdom_building_id)) {
            $gameBuilding = GameBuilding::find($quest->required_kingdom_building_id);

            $count = $character->kingdoms()->whereHas('buildings', function ($query) use ($gameBuilding, $quest) {
                $query->where('game_building_id', $gameBuilding->id)
                    ->where('level', '>=', $quest->required_kingdom_building_level);
            })->count();

            if ($count > 0) {
                $this->finishedRequirements[] = 'required_kingdom_building_level';
            }
        }

        return $this;
    }

    /**
     * Does the character have a combined count across all kingdoms of units, for the required amount?
     */
    public function requiredKingdomUnitCount(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_kingdom_units)) {
            foreach ($character->kingdoms as $kingdom) {
                if ($kingdom->units->sum('amount') >= $quest->required_kingdom_units) {
                    $this->finishedRequirements[] = 'required_kingdom_units';

                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Does the character meet the required kingdom passive level?
     */
    public function requiredKingdomPassiveLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {

        if (! is_null($quest->required_passive_skill) && ! is_null($quest->required_passive_level)) {
            $requiredSkill = $character->passiveSkills()->where('passive_skill_id', $quest->required_passive_skill)->first();

            if ($requiredSkill->current_level >= $quest->required_passive_level) {
                $this->finishedRequirements[] = 'required_passive_level';
            }
        }

        return $this;
    }

    /**
     * Does the character have a specific class rank skill equiped?
     */
    public function requiredClassRanksEquipped(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {

        if (! is_null($quest->required_class_specials_equipped)) {
            if ($character->classSpecialsEquipped()->count() >= $quest->required_class_specials_equipped) {
                $this->finishedRequirements[] = 'required_class_specials_equipped';
            }
        }

        return $this;
    }

    /**
     * Does the character match the required class rank level in some class rank?
     */
    public function requiredClassRankLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_class_rank_level)) {

            $classRank = $character->classRanks->where('level', '>=', $quest->required_class_rank_level)->first();

            if (! is_null($classRank)) {
                $this->finishedRequirements[] = 'required_class_rank_level';
            }
        }

        return $this;
    }

    /**
     * Does the character have the required currency?
     */
    public function requiredCurrency(Character $character, GuideQuest $quest, string $currency): GuideQuestRequirementsService
    {
        if (! is_null($quest->{'required_'.$currency})) {
            if ($character->{$currency} >= $quest->{'required_'.$currency}) {
                $this->finishedRequirements[] = 'required_'.$currency;
            }
        }

        return $this;
    }

    /**
     * Does the character have the required total stats
     */
    public function requiredTotalStats(Character $character, GuideQuest $quest, array $stats): GuideQuestRequirementsService
    {
        if (! is_null($quest->required_stats)) {

            $completedStats = [];

            foreach ($stats as $stat) {
                $value = $character->getInformation()->statMod($stat);

                if ($value >= $quest->required_stats) {
                    $completedStats[] = $stat;
                }
            }

            if (count($completedStats) === count($stats)) {
                $this->finishedRequirements[] = 'required_stats';
            }
        }

        return $this;
    }

    /**
     * Does the character have a specific stat at a specific number?
     */
    public function requiredStats(Character $character, GuideQuest $quest, array $stats): GuideQuestRequirementsService
    {
        foreach ($stats as $stat) {
            $questStat = $quest->{'required_'.$stat};

            if (! is_null($questStat)) {
                $value = $character->getInformation()->statMod($stat);

                if ($value >= $questStat) {
                    $this->finishedRequirements[] = 'required_'.$stat;
                }
            }
        }

        return $this;
    }

    /**
     * Is the player actually on the map we want them to be?
     *
     * @return $this
     */
    public function requirePlayerToBeOnASpecificMap(Character $character, GuideQuest $quest): GuideQuestRequirementsService
    {

        if ($character->map->game_map_id === $quest->be_on_game_map) {
            $this->finishedRequirements[] = 'required_to_be_on_game_map_name';
        }

        return $this;
    }

    /**
     * Has the player participated in the required kill amount?
     *
     * @return $this
     */
    public function requiredGlobalEventKillAmount(Character $character, GuideQuest $guideQuest): GuideQuestRequirementsService
    {

        if (is_null($character->globalEventKills)) {
            return $this;
        }

        if ($character->globalEventKills->kills >= $guideQuest->required_event_goal_participation) {
            $this->finishedRequirements[] = 'required_event_goal_participation';
        }

        return $this;
    }

    /**
     * Has the character leveled their class skill to the desired level?
     */
    protected function classSkillCheck(Character $character, GuideQuest $quest): void
    {
        $classSkill = $character->skills()->whereHas('baseSkill', function ($query) use ($character) {
            $query->whereNotNull('game_class_id')
                ->where('game_class_id', $character->class->id);
        })->first();

        if (! is_null($classSkill)) {
            if ($classSkill->level >= $quest->required_skill_type_level) {
                $this->finishedRequirements[] = 'required_skill_type_level';
            }
        }
    }

    /**
     * Has the character leveled the crafting skill type to a specified level?
     */
    protected function craftingSkillTypeCheck(Character $character, GuideQuest $quest): void
    {
        $classSkill = $character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('type', SkillTypeValue::CRAFTING);
        })->where('level', '>=', $quest->required_skill_type_level)->first();

        if (! is_null($classSkill)) {
            $this->finishedRequirements[] = 'required_skill_type_level';
        }
    }

    /**
     * Checks to see if the item  fr the guide quest exists on a completed quest that we handed in.
     */
    protected function wasItemUsedInQuest(Character $character, int $itemId): bool
    {
        return $character->questsCompleted()
            ->whereHas('quest', function ($query) use ($itemId) {
                $query->where(function ($q) use ($itemId) {
                    $q->where('item_id', $itemId)
                        ->orWhere('secondary_required_item', $itemId);
                });
            })
            ->exists();
    }
}
