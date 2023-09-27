<?php

namespace App\Game\GuideQuests\Services;

use Exception;
use App\Flare\Models\GameMap;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use Illuminate\Support\Facades\Log;
use App\Game\Skills\Values\SkillTypeValue;

class GuideQuestRequirementsService {

    private array $finishedRequirements = [];

    public function getFinishedRequirements(): array {
        return $this->finishedRequirements;
    }

    public function resetFinishedRequirements(): void {
        $this->finishedRequirements = [];
    }

    public function requiredLevelCheck(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_level)) {
            if ($character->level >= $quest->required_level) {
                $this->finishedRequirements[] = 'required_level';
            }
        }

        return $this;
    }

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

    public function requiredFactionLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_faction_id)) {
            $faction = $character->factions()->where('game_map_id', $quest->required_faction_id)->first();

            if ($faction->current_level >= $quest->required_faction_level) {
                $this->finishedRequirements[] = 'required_faction_level';
            }
        }

        return $this;
    }

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

    public function requiredQuest(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_quest_id)) {
            $canHandIn = !is_null($character->questsCompleted()->where('quest_id', $quest->required_quest_id)->first());

            if ($canHandIn) {
                $this->finishedRequirements[] = 'required_quest_id';
            }
        }

        return $this;
    }

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

    public function requiredKingdomCount(Character $character, GuideQuest $quest): GuideQuestRequirementsService {
        if (!is_null($quest->required_kingdoms)) {
            if ($character->kingdoms->count() >= $quest->required_kingdoms) {
                $this->finishedRequirements[] = 'required_kingdoms';
            }
        }

        return $this;
    }

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

    public function requiredKingdomPassiveLevel(Character $character, GuideQuest $quest): GuideQuestRequirementsService {

        if (!is_null($quest->required_passive_skill) && !is_null($quest->required_passive_level)) {
            $requiredSkill = $character->passiveSkills()->where('passive_skill_id', $quest->required_passive_skill)->first();

            if ($requiredSkill->current_level >= $quest->required_passive_level) {
                $this->finishedRequirements[] = 'required_passive_level';
            }
        }

        return $this;
    }

    public function requiredClassRanksEquipped(Character $character, GuideQuest $quest): GuideQuestRequirementsService {

        if (!is_null($quest->required_class_specials_equipped)) {
            if ($character->classSpecialsEquipped()->count() >= $quest->required_class_specials_equipped) {
                $this->finishedRequirements[] = 'required_class_specials_equipped';
            }
        }

        return $this;
    }

    public function requiredCurrency(Character $character, GuideQuest $quest, string $currency): GuideQuestRequirementsService {
        if (!is_null($quest->{'required_' . $currency})) {
            if ($character->{$currency} >= $quest->{'required_' . $currency}) {
                $this->finishedRequirements[] = 'required_' . $currency;
            }
        }

        return $this;
    }

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

    protected function craftingSkillTypeCheck(Character $character, GuideQuest $quest): void {
        $classSkill = $character->skills()->whereHas('baseSkill', function ($query) use ($character) {
            $query->where('type', SkillTypeValue::CRAFTING);
        })->first();

        if (!is_null($classSkill)) {
            if ($classSkill->level >= $quest->required_skill_type_level) {
                $this->finishedRequirements[] = 'required_skill_type_level';
            }
        }
    }
}
