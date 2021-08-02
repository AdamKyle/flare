<?php

namespace App\Admin\Services;

use App\Flare\Mail\GenericMail;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use Facades\App\Flare\Values\UserOnlineValue;
use Mail;

class AssignSkillService {

    /**
     * Assign the skill or throw an exception.
     *
     * `$for` should be of: `all`, `select-monsters` or `select-class`
     * anything else, throws an error.
     *
     * @param string $for
     * @param GameSkill $skill
     * @param int $monsterId | null
     * @param int $classId | null
     * @throws Exception
     * @return void
     */
    public function assignSkill(string $for, GameSkill $skill, int $monsterId = null) {
        switch($for) {
            case 'all':
                $this->assignSkillToCharacters($skill);
                $this->assignSkillToMonsters($skill);
                return;
            case 'select-monsters':
                $this->assignSkillToMonster($skill, $monsterId);
                return;
            case 'select-class':
                $this->assignSkillToClasses($skill);
                return;
            case 'only-characters':
                $this->assignSkillToCharacters($skill);
                return;
            default:
                throw new \Exception('Could not determine who to assign skill to. $for: ' . $for);
        }
    }

    protected function alertUser(Character $character, GameSkill $skill): void {
        if (UserOnlineValue::isOnline($character->user)) {
            event(new ServerMessageEvent($character->user, 'new-skill', $skill->name));
        } else {
            $message = 'You were given a new skill by The Creator. Head your character sheet to see the new skill: ' . $skill->name;

            Mail::to($character->user->email)->send(new GenericMail($character->user, $message, 'New character skill'));
        }
    }

    protected function assignSkillToClasses(GameSkill $skill) {
        Character::where('game_class_id', $skill->game_class_id)->chunkById(1000, function($characters) use($skill) {
            foreach ($characters as $character) {

                $character->skills()->create([
                    'character_id' => $character->id,
                    'game_skill_id' => $skill->id,
                    'currently_training' => false,
                    'level' => 1,
                    'xp_max' => $skill->can_train ? rand(100, 150) : rand(100, 200),
                ]);

                $this->alertUser($character, $skill);
            }
        });
    }

    protected function assignSkillToCharacters(GameSkill $skill) {
        Character::chunkById(1000, function($characters) use ($skill) {
            foreach ($characters as $character) {

                $character->skills()->create([
                    'character_id' => $character->id,
                    'game_skill_id' => $skill->id,
                    'currently_training' => false,
                    'level' => 1,
                    'xp_max' => $skill->can_train ? rand(100, 150) : rand(100, 200),
                    'is_locked' => $skill->is_locked,
                ]);

                $this->alertUser($character, $skill);
            }
        });
    }

    protected function assignSkillToMonsters(GameSkill $skill) {

        Monster::chunkById(1000, function($monsters) use ($skill) {
            foreach ($monsters as $monster) {
                $foundSkill = $monster->skills->where('game_skill_id', $skill->id)->first();

                if (!is_null($foundSkill)) {
                    continue;
                }

                $monster->skills()->create([
                    'monster_id' => $monster->id,
                    'game_skill_id' => $skill->id,
                    'currently_training' => false,
                    'level' => 1,
                ]);
            }
        });
    }

    protected function assignSkillToMonster(GameSkill $skill, int $monsterId) {
        $monster = Monster::find($monsterId);

        if (is_null($monster)) {
            throw new \Exception('Monster not found for id: ' . $monsterId);
        }

        $foundSkill = $monster->skills->where('game_skill_id', $skill->id)->first();

        if (!is_null($foundSkill)) {
            return null;
        }

        $monster->skills()->create([
            'monster_id' => $monster->id,
            'game_skill_id' => $skill->id,
            'currently_training' => false,
            'level' => 0,
        ]);
    }
}
