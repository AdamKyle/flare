<?php

namespace App\Game\GuideQuests\Services;

use Exception;
use App\Flare\Models\GameMap;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use Illuminate\Support\Facades\Log;
use App\Game\Skills\Values\SkillTypeValue;

class GuideQuestRequirementsService {

    /**
     * @var array $finishedRequirements
     */
    private array $finishedRequirements = [];

    /**
     * Get the finished requirements.
     *
     * @return array
     */
    public function getFinishedRequirements(): array {
        return $this->finishedRequirements;
    }

    /**
     * Reset the finished requirements.
     *
     * @return void
     */
    public function resetFinishedRequirements(): void {
        $this->finishedRequirements = [];
    }

    /**
     * Does character meet the required level?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredLevelCheck(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_level)) {
            if ($character->level >= $quest->required_level) {
                $this->finishedRequirements[] = 'required_level';
            }
        }

        return $this;
    }

    /**
     * Does character have the required skill  attributes?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param boolean $primary
     * @return GuideQuestRequirementsService
     */
    public function requiredSkillCheck(Character $character, GuideQuest $quest, bool $primary = true): GuideQuestRequirementsService {
        $attribute = $primary ? 'required_skill' : 'required_secondary_skill';

        if (!is_null($quest->{$attribute})) {
            $requiredSkill = $character->skills()->where('game_skill_id', $quest->{$attribute})->first();

            $attribute = $primary ? 'required_skill_level' : 'required_secondary_skill_level';

            if ($requiredSkill->level >= $quest->{$attribute}) {
                $this->finishedRequirements[] = $attribute;
            }
        }

        return $this;
    }

    /**
     * Does the character have the require mercenary attributes?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param boolean $primary
     * @return GuideQuestRequirementsService
     */
    public function requiredMercenaryCheck(Character $character, GuideQuest $quest, bool $primary = true): GuideQuestRequirementsService {
        $attribute = $primary ? 'required_mercenary_type' : 'required_secondary_mercenary_type';

        if (!is_null($quest->{$attribute})) {
            $requiredMercenary = $character->mercenaries()->where('mercenary_type', $quest->{$attribute})->first();

            if (is_null($requiredMercenary)) {
                return $this;
            }

            $this->finishedRequirements[] = $attribute;

            $levelAttribute = $primary ? 'required_mercenary_level' : 'required_secondary_mercenary_level';

            if ($requiredMercenary->current_level >= $quest->{$levelAttribute}) {
                $this->finishedRequirements[] = $levelAttribute;
            }
        }

        return $this;
    }

    /**
     * Does character have the required skill type?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredSkillTypeCheck(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_skill_type)) {
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
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredFactionLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_faction_id)) {
            $faction = $character->factions()->where('game_map_id', $quest->required_faction_id)->first();

            if ($faction->current_level >= $quest->required_faction_level) {
                $this->finishedRequirements[] = 'required_faction_level';
            }
        }

        return $this;
    }

    /**
     * Does character have access to a specific map?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredGameMapAccess(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_game_map_id)) {
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
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredQuest(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_quest_id)) {
            $canHandIn = !is_null($character->questsCompleted()->where('quest_id', $quest->required_quest_id)->first());

            if ($canHandIn) {
                $this->finishedRequirements[] = 'required_quest_id';
            }
        }

        return $this;
    }

    /**
     * Does character have a specific quest item?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param boolean $primary
     * @return GuideQuestRequirementsService
     */
    public function requiredQuestItem(Character $character, GuideQuest $quest, bool $primary = true): GuideQuestRequirementsService {
        $attribute = $primary ? 'required_quest_item_id' : 'secondary_quest_item_id';

        if (!is_null($quest->{$attribute})) {
            $canHandIn = $character->inventory->slots->filter(function ($slot) use ($quest, $attribute) {
                return $slot->item->type === 'quest' && $slot->item->id === $quest->{$attribute};
            })->isNotEmpty();

            if ($canHandIn) {
                $this->finishedRequirements[] = $attribute;
            }
        }

        return $this;
    }

    /**
     * Does character have the required kingdom count?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredKingdomCount(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_kingdoms)) {
            if ($character->kingdoms->count() >= $quest->required_kingdoms) {
                $this->finishedRequirements[] = 'required_kingdoms';
            }
        }

        return $this;
    }

    /**
     * Does the combined levels of all buildings across all kingdoms meet or exceed the required?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredKingdomBuildingLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_kingdom_level)) {
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
     * Does the character have a combined count across all kingdoms of units, for the required amount?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredKingdomUnitCount(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_kingdom_units)) {
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
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredKingdomPassiveLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService {

        if (!is_null($quest->required_passive_skill) && !is_null($quest->required_passive_level)) {
            $requiredSkill = $character->passiveSkills()->where('passive_skill_id', $quest->required_passive_skill)->first();

            if ($requiredSkill->current_level >= $quest->required_passive_level) {
                $this->finishedRequirements[] = 'required_passive_level';
            }
        }

        return $this;
    }

    /**
     * Does the character have a specific class rank skill equiped?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredClassRanksEquipped(Character $character, GuideQuest $quest): GuideQuestRequirementsService {

        if (!is_null($quest->required_class_specials_equipped)) {
            if ($character->classSpecialsEquipped()->count() >= $quest->required_class_specials_equipped) {
                $this->finishedRequirements[] = 'required_class_specials_equipped';
            }
        }

        return $this;
    }

    /**
     * Does the character match the required class rank level in some class rank?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return GuideQuestRequirementsService
     */
    public function requiredClassRankLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_class_rank_level)) {

            $classRank = $character->classRanks->where('level', '>=', $quest->required_class_rank_level)->first();

            if (!is_null($classRank)) {
                $this->finishedRequirements[] = 'required_class_rank_level';
            }
        }

        return $this;
    }

    /**
     * Does the character have the required currency?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param string $currency
     * @return GuideQuestRequirementsService
     */
    public function requiredCurrency(Character $character, GuideQuest $quest, string $currency): GuideQuestRequirementsService {
        if (!is_null($quest->{'required_' . $currency})) {
            if ($character->{$currency} >= $quest->{'required_' . $currency}) {
                $this->finishedRequirements[] = 'required_' . $currency;
            }
        }

        return $this;
    }

    /**
     * Does the character have the required total stats
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param array $stats
     * @return GuideQuestRequirementsService
     */
    public function requiredTotalStats(Character $character, GuideQuest $quest, array $stats): GuideQuestRequirementsService {
        if (!is_null($quest->required_stats)) {

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
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param array $stats
     * @return GuideQuestRequirementsService
     */
    public function requiredStats(Character $character, GuideQuest $quest, array $stats): GuideQuestRequirementsService {
        foreach ($stats as $stat) {
            $questStat = $quest->{'required_' . $stat};

            if (!is_null($questStat)) {
                $value = $character->getInformation()->statMod($stat);

                if ($value >= $questStat) {
                    $this->finishedRequirements[] = 'required_' . $stat;
                }
            }
        }

        return $this;
    }

    /**
     * Has the character leveled their class skill to the desired level?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return void
     */
    protected function classSkillCheck(Character $character, GuideQuest $quest): void {
        $classSkill = $character->skills()->whereHas('baseSkill', function ($query) use ($character) {
            $query->whereNotNull('game_class_id')
                ->where('game_class_id', $character->class->id);
        })->first();

        if (!is_null($classSkill)) {
            if ($classSkill->level >= $quest->required_skill_type_level) {
                $this->finishedRequirements[] = 'required_skill_type_level';
            }
        }
    }

    /**
     * Has the character leveled the crafting skill type to a specified level?
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return void
     */
    protected function craftingSkillTypeCheck(Character $character, GuideQuest $quest): void {
        $classSkill = $character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('type', SkillTypeValue::CRAFTING);
        })->where('level', '>=', $quest->required_skill_type_level)->first();

        if (!is_null($classSkill)) {
            $this->finishedRequirements[] = 'required_skill_type_level';
        }
    }
}
