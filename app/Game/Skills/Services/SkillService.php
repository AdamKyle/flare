<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\Skill;
use App\Flare\Transformers\BasicSkillsTransformer;
use App\Flare\Transformers\SkillsTransformer;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Events\Values\EventType;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class SkillService
{
    use ResponseBuilder;

    /**
     * @var Skill|null $skillInTraining
     */
    private ?Skill $skillInTraining;

    /**
     * @param Manager $manager
     * @param BasicSkillsTransformer $basicSkillsTransformer
     * @param SkillsTransformer $skillsTransformer
     * @param UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes
     * @param BattleMessageHandler $battleMessageHandler
     */
    public function __construct(
        private readonly Manager                           $manager,
        private readonly BasicSkillsTransformer            $basicSkillsTransformer,
        private readonly SkillsTransformer                 $skillsTransformer,
        private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes,
        private readonly BattleMessageHandler              $battleMessageHandler,
    ) {}

    /**
     * Set the current skill in training
     *
     * @param Character $character
     * @return SkillService
     */
    public function setSkillInTraining(Character $character): SkillService
    {
        $this->skillInTraining = $character->skills->where('currently_training', true)->first();

        return $this;
    }

    /**
     * Gets the skills for a player.
     *
     * @param Character $character
     * @param array $gameSkillIds
     * @return array
     */
    public function getSkills(Character $character, array $gameSkillIds): array
    {
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
    public function getSkill(Skill $skill): array
    {
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
    public function trainSkill(Character $character, int $skillId, float $xpPercentage): array
    {
        // Find the skill we want to train.
        $skill = $character->skills->filter(function ($skill) use ($skillId) {
            return $skill->id === $skillId && ! $skill->is_hidden;
        })->first();

        if (is_null($skill)) {
            return $this->errorResult('Invalid Input.');
        }

        $skillCurrentlyTraining = $character->skills->filter(function ($skill) {
            return $skill->currently_training;
        })->first();

        if (! is_null($skillCurrentlyTraining)) {
            $skillCurrentlyTraining->update([
                'currently_training' => false,
                'xp_towards' => 0.0,
            ]);
        }

        // Begin training
        $skill->update([
            'currently_training' => true,
            'xp_towards' => $xpPercentage,
            'xp_max' => is_null($skill->xp_max) ? rand(100, 150) : $skill->xp_max,
        ]);

        return $this->successResult([
            'message' => 'You are now training: ' . $skill->name,
        ]);
    }

    /**
     * Assign XP to a training skill.trainSkill
     *
     * @param Character $character
     * @param int $xp
     * @return void
     * @throws Exception
     */
    public function assignXPToTrainingSkill(Character $character, int $xp): void
    {
        if (is_null($this->skillInTraining)) {
            return;
        }

        $skillXp = $this->getXpForSkillIntraining($character, $xp);

        $newXp = $this->skillInTraining->xp + $skillXp;

        $this->skillInTraining->update(['xp' => $newXp]);
        $skillInTraining = $this->skillInTraining->refresh();

        $this->battleMessageHandler->handleSkillXpUpdate($character->user, $skillInTraining->name, $skillXp);

        $this->handlePossibleLevelUpForSkill($skillInTraining, $newXp);
    }

    /**
     * Give a specific amount of xp to a skill in training
     *
     * @param Character $character
     * @param integer $totalXpToGive
     * @return void
     */
    public function giveXpToTrainingSkill(Character $character, int $totalXpToGive): void
    {
        if (is_null($this->skillInTraining)) {
            return;
        }

        $newXp = $this->skillInTraining->xp + $totalXpToGive;

        $this->skillInTraining->update(['xp' => $newXp]);
        $skillInTraining = $this->skillInTraining->refresh();

        $this->battleMessageHandler->handleSkillXpUpdate($character->user, $skillInTraining->name, $totalXpToGive);

        $this->handlePossibleLevelUpForSkill($skillInTraining, $newXp);
    }

    /**
     * Get the xp for the skill in training
     *
     * @param Character $character
     * @param integer $xp
     * @return integer
     */
    public function getXpForSkillIntraining(Character $character, int $xp): int
    {
        $event = ScheduledEvent::where('event_type', EventType::FEEDBACK_EVENT)->where('currently_running', true)->first();

        if (is_null($this->skillInTraining)) {
            return 0;
        }

        if ($this->skillInTraining->level === $this->skillInTraining->baseSkill->max_level) {
            return 0;
        }

        $skillXp = $xp + ($xp * $this->skillInTraining->xp_towards);
        $skillXp = $skillXp + $skillXp * ($this->skillInTraining->skill_training_bonus + $character->map->gameMap->skill_training_bonus);
        $skillXp += 5;

        if (!is_null($event)) {
            $skillXp += 150;
        }

        return $skillXp;
    }

    /**
     * Get the XP after being reduced from any skill in training.
     *
     * @param Character $character
     * @param integer $xp
     * @return integer
     */
    public function getCharacterXpWithSkillTrainingReduction(Character $character, int $xp): int
    {
        if (is_null($this->skillInTraining)) {
            return $xp;
        }

        if ($this->skillInTraining->level === $this->skillInTraining->baseSkill->max_level) {
            return $xp;
        }

        return intval($xp - ($xp * $this->skillInTraining->xp_towards));
    }

    /**
     * Assign XP to crafting skills.
     *
     * - Uses a base of 25
     * - Applies skill training bonuses
     * - Applies Game Map Bonuses
     *
     * @param GameMap $gameMap
     * @param Skill $skill
     * @throws Exception
     */
    public function assignXpToCraftingSkill(GameMap $gameMap, Skill $skill): void
    {
        if ($skill->level >= 400) {
            $skill->update(['xp' => 0]);
            return;
        }

        $xp = 25;
        $xp = $xp + $xp * ($skill->skill_training_bonus + $gameMap->skill_training_bonus);

        $newXp = $skill->xp + $xp;

        $event = ScheduledEvent::where('event_type', EventType::FEEDBACK_EVENT)
            ->where('currently_running', true)
            ->first();

        if (!is_null($event)) {
            if ($skill->type()->isEnchanting() || $skill->type()->isCrafting() || $skill->type()->isAlchemy() || $skill->type()->isGemCrafting()) {
                $newXp += 175;
            } else {
                $newXp += 150;
            }
        }

        while ($newXp >= $skill->xp_max) {
            $skill->update(['xp' => $skill->xp_max]);

            $newXp -= $skill->xp_max;
            $skill = $skill->refresh();

            $skill = $this->levelUpSkill($skill);

            if ($skill->level >= 400) {
                $newXp = 0;
                break;
            }
        }

        $skill->update(['xp' => $newXp]);
    }

    /**
     * Handle possibly leveling up the skill.
     *
     * @param Skill $skillInTraining
     * @param integer $newXp
     * @return void
     * @throws Exception
     */
    private function handlePossibleLevelUpForSkill(Skill $skillInTraining, int $newXp): void
    {
        while ($newXp >= $skillInTraining->xp_max) {
            $newXp -= $skillInTraining->xp_max;

            $skillInTraining = $this->levelUpSkill($skillInTraining);

            if ($skillInTraining->level === $skillInTraining->baseSkill->max_level) {
                $newXp = 0;

                $skillInTraining->update(['xp' => $newXp]);

                $skillInTraining = $skillInTraining->refresh();

                break;
            }

            $skillInTraining->update(['xp' => $newXp]);

            $skillInTraining = $skillInTraining->refresh();
        }

        if ($newXp > 0) {
            $skillInTraining->update(['xp' => $newXp]);
        }
    }

    /**
     * Level a skill.
     *
     * @param Skill $skill
     * @return Skill
     * @throws Exception
     */
    private function levelUpSkill(Skill $skill): Skill
    {
        if ($skill->xp >= $skill->xp_max) {

            $level = $skill->level + 1;

            $bonus = $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level;

            if ($skill->baseSkill->max_level === $level) {
                $bonus = 1.0;
            }

            $skill->update([
                'level' => $level,
                'xp_max' => $skill->can_train ? $level * 10 : rand(100, 350),
                'base_damage_mod' => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                'base_healing_mod' => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                'base_ac_mod' => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                'move_time_out_mod' => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                'skill_bonus' => $bonus,
                'xp' => 0,
            ]);

            $character = $skill->character->refresh();

            event(new SkillLeveledUpServerMessageEvent($skill->character->user, $skill->refresh()));

            if ($skill->can_train) {
                $this->updateCharacterAttackTypes->updateCache($character);
            }
        }

        return $skill->refresh();
    }
}
