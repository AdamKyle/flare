<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Game\Core\Traits\ResponseBuilder;

class SkillService {

    use ResponseBuilder;

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
            return $skill->id === $skillId;
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

        event(new UpdateTopBarEvent($character));

        return $this->successResult([
            'message' => 'You are now training ' . $skill->name
        ]);
    }
}