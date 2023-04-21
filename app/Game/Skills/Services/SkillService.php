<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\GameMap;
use App\Flare\Models\Skill;
use App\Flare\Models\Character;
use App\Flare\Transformers\BasicSkillsTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class SkillService {

    use ResponseBuilder;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var BasicSkillsTransformer $skillsTransformer
     */
    private BasicSkillsTransformer $basicSkillsTransformer;

    /**
     * @var SkillsTransformer $skillsTransformer
     */
    private SkillsTransformer $skillsTransformer;

    /**
     * @var UpdateCharacterAttackTypes $updateCharacterAttackTypes
     */
    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    /**
     * @param Manager $manager
     * @param BasicSkillsTransformer $basicSkillsTransformer
     * @param SkillsTransformer $skillsTransformer
     * @param UpdateCharacterAttackTypes $updateCharacterAttackTypes
     */
    public function __construct(Manager $manager,
                                BasicSkillsTransformer $basicSkillsTransformer,
                                SkillsTransformer $skillsTransformer,
                                UpdateCharacterAttackTypes $updateCharacterAttackTypes
    ) {
        $this->manager                    = $manager;
        $this->basicSkillsTransformer     = $basicSkillsTransformer;
        $this->skillsTransformer          = $skillsTransformer;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    /**
     * Gets the skills for a player.
     *
     * @param Character $character
     * @param array $gameSkillIds
     * @return array
     */
    public function getSkills(Character $character, array $gameSkillIds): array {
        $skills = $character->skills()->whereIn('game_skill_id', $gameSkillIds)->where('is_hidden', false)->get();

        $skills = new Collection($skills, $this->basicSkillsTransformer);

        return $this->manager->createData($skills)->toArray();
    }

    /**
     * Fetch Skill Info.
     *
     * @param Skill $skill
     * @return array
     */
    public function getSkill(Skill $skill): array {
        $skill = new Item($skill, $this->skillsTransformer);

        return $this->manager->createData($skill)->toArray();
    }

    /**
     * Sets a skill to training.
     *
     * If a skill is in training, remove it from training.
     *
     * @param Character $character
     * @param int $skillId
     * @param float $xpPercentage
     * @return array
     */
    public function trainSkill(Character $character, int $skillId, float $xpPercentage): array {
        // Find the skill we want to train.
        $skill = $character->skills->filter(function ($skill) use($skillId) {
            return $skill->id === $skillId && !$skill->is_hidden;
        })->first();

        if (is_null($skill)) {
            return $this->errorResult('Invalid Input.');
        }

        $skillCurrentlyTraining = $character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();

        if (!is_null($skillCurrentlyTraining)) {
            $skillCurrentlyTraining->update([
                'currently_training' => false,
                'xp_towards'         => 0.0,
            ]);
        }

        // Begin training
        $skill->update([
            'currently_training' => true,
            'xp_towards'         => $xpPercentage,
            'xp_max'             => is_null($skill->xp_max) ? rand(100, 150) : $skill->xp_max,
        ]);

        return $this->successResult([
            'message' => 'You are now training ' . $skill->name
        ]);
    }

    /**
     * Assign XP to a training skill.
     *
     * @param Character $character
     * @param int $xp
     * @return void
     * @throws Exception
     */
    public function assignXPToTrainingSkill(Character $character, int $xp): void {
        $skillInTraining = $character->skills()->where('currently_training', true)->first();

        if (is_null($skillInTraining)) {
            return;
        }

        if ($skillInTraining->level === $skillInTraining->baseSkill->max_level) {
            return;
        }

        $skillXp     = $xp + ($xp * $skillInTraining->xp_towards);
        $skillXp     = $skillXp + $skillXp * ($skillInTraining->skill_training_bonus + $character->map->gameMap->skill_training_bonus);
        $skillXp     += 5;

        $skillInTraining->update([
            'xp' => $skillInTraining->xp + $skillXp
        ]);

        $skillInTraining = $skillInTraining->refresh();

        $this->levelUpSkill($skillInTraining);
    }

    /**
     * Assign xp to crafting skills.
     *
     * - Uses a base of 25
     * - Applies skill training bonuses
     * - Applies Game Map Bonuses
     *
     * @param GameMap $gameMap
     * @param Skill $skill
     * @return void
     * @throws Exception
     */
    public function assignXpToCraftingSkill(GameMap $gameMap, Skill $skill): void {

        if ($skill->level >= 400) {
            return;
        }

        $xp = 25;
        $xp = $xp + $xp * ($skill->skill_training_bonus + $gameMap->skill_training_bonus);

        $skill->update([
            'xp' => $skill->xp + $xp,
        ]);

        $skill = $skill->refresh();

        $this->levelUpSkill($skill);
    }

    /**
     * Level a skill.
     *
     * @param Skill $skill
     * @return void
     * @throws Exception
     */
    protected function levelUpSkill(Skill $skill): void {
        if ($skill->xp >= $skill->xp_max) {
            $level = $skill->level + 1;

            $bonus = $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level;

            if ($skill->baseSkill->max_level === $level) {
                $bonus = 1.0;
            }

            $skill->update([
                'level'              => $level,
                'xp_max'             => $skill->can_train ? $level * 10 : rand(100, 350),
                'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                'skill_bonus'        => $bonus,
                'xp'                 => 0,
            ]);

            $character = $skill->character->refresh();

            event(new SkillLeveledUpServerMessageEvent($skill->character->user, $skill->refresh()));

            if ($skill->can_train) {
                $this->updateCharacterAttackTypes->updateCache($character);
            }
        }
    }
}
