<?php

namespace App\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Illuminate\Support\Facades\Log;

class SkillGuideQuestRequirementService
{
    /**
     * Return completed Skill requirement keys.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return array
     */
    public function completedRequirements(Character $character, GuideQuest $quest): array
    {
        return array_values(array_filter([
            $this->trainingSkill($character, $quest),
            $this->skill($character, $quest, true),
            $this->skill($character, $quest, false),
            $this->skillType($character, $quest),
        ]));
    }

    /**
     * Return the completed active Training Skill requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function trainingSkill(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_training_skill) || is_null($quest->required_training_xp_sacrifice)) {
            return null;
        }

        $skill = $character->skills()->where('game_skill_id', $quest->required_training_skill)->first();
        $complete = ! is_null($skill)
            && $skill->currently_training
            && abs($skill->xp_towards - $quest->required_training_xp_sacrifice) < 0.00001;

        return $complete ? 'required_training_xp_sacrifice' : null;
    }

    /**
     * Return the completed primary or secondary Skill requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @param bool $primary
     * @return string|null
     */
    private function skill(Character $character, GuideQuest $quest, bool $primary): ?string
    {
        $skillAttribute = $primary ? 'required_skill' : 'required_secondary_skill';
        $levelAttribute = $primary ? 'required_skill_level' : 'required_secondary_skill_level';

        if (is_null($quest->{$skillAttribute})) {
            return null;
        }

        $skill = $character->skills()->where('game_skill_id', $quest->{$skillAttribute})->first();

        return ! is_null($skill) && $skill->level >= $quest->{$levelAttribute} ? $levelAttribute : null;
    }

    /**
     * Return the completed Skill-type requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function skillType(Character $character, GuideQuest $quest): ?string
    {
        if (is_null($quest->required_skill_type)) {
            return null;
        }

        try {
            $skillType = SkillTypeValue::tryFrom($quest->required_skill_type);

            if (is_null($skillType)) {
                throw new Exception('Invalid Skill Type Value for: '.$quest->required_skill_type);
            }

            if ($skillType->effectsClassSkills()) {
                return $this->classSkillCheck($character, $quest);
            }

            if ($skillType->isCrafting()) {
                return $this->craftingSkillTypeCheck($character, $quest);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
        }

        return null;
    }

    /**
     * Return the completed class Skill-type requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function classSkillCheck(Character $character, GuideQuest $quest): ?string
    {
        $skill = $character->skills()->whereHas('baseSkill', function ($query) use ($character) {
            $query->whereNotNull('game_class_id')->where('game_class_id', $character->class->id);
        })->first();

        return ! is_null($skill) && $skill->level >= $quest->required_skill_type_level
            ? 'required_skill_type_level'
            : null;
    }

    /**
     * Return the completed crafting Skill-type requirement key.
     *
     * @param Character $character
     * @param GuideQuest $quest
     * @return string|null
     */
    private function craftingSkillTypeCheck(Character $character, GuideQuest $quest): ?string
    {
        $skill = $character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('type', SkillTypeValue::CRAFTING->value);
        })->where('level', '>=', $quest->required_skill_type_level)->first();

        return is_null($skill) ? null : 'required_skill_type_level';
    }
}
