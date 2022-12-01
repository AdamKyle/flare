<?php

namespace App\Game\ClassRanks\Services;

use App\Flare\Models\Character;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;

class ClassRankService {

    /**
     * give xp to a class rank for the characters current class.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function giveXpToClassRank(Character $character): void {
        $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();

        if (is_null($classRank)) {
            throw new Exception('No Class Rank Found for character: ' . $character->name . ' for id: ' . $character->ghame_class_id);
        }

        if ($classRank->level >= ClassRankValue::MAX_LEVEL) {
            return;
        }

        $classRank->update([
            'current_xp' => $classRank->current_xp + ClassRankValue::XP_PER_KILL,
        ]);

        $classRank = $classRank->refresh();

        if ($classRank->current_xp >= $classRank->required_xp) {
             $classRank->update([
                 'level'      => $classRank->level + 1,
                 'current_xp' => 0,
             ]);

             event(new ServerMessageEvent('You gained a new class rank in: ' . $character->class->name));
        }
    }
}
