<?php

namespace App\Game\ClassRanks\Controllers\Api;

use App\Flare\Models\Character;
use App\Http\Controllers\Controller;

class ClassRanksController extends Controller {

    public function getCharacterClassRanks(Character $character) {

        $classRanks = $character->classRanks;

        $classRanks  = $classRanks->transform(function($classRank) use($character) {

            $classRank->class_name = $classRank->gameClass->name;

            $classRank->is_active  = $classRank->gameClass->id === $character->game_class_id;

            $classRank->is_locked  = false;

            return $classRank;
        });

        return response()->json(['class_ranks' => $classRanks->toArray()]);
    }

}
