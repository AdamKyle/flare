<?php

namespace App\Game\ClassRanks\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\GameClassSpecial;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Http\Controllers\Controller;

class ClassRanksController extends Controller {

    public function getCharacterClassRanks(Character $character) {

        $classRanks = $character->classRanks()->with(['gameClass', 'weaponMasteries'])->get();

        $classRanks  = $classRanks->transform(function($classRank) use($character) {

            $classRank->class_name = $classRank->gameClass->name;

            $classRank->is_active  = $classRank->gameClass->id === $character->game_class_id;

            $classRank->is_locked  = false;

            $classRank->weapon_masteries = $classRank->weaponMasteries->transform(function($weaponMastery) {

                $weaponMastery->mastery_name = (new WeaponMasteryValue($weaponMastery->weapon_type))->getName();

                return $weaponMastery;
            });

            return $classRank;
        })->sortByDesc(function($item) {
            return $item->is_active;
        })->all();

        return response()->json(['class_ranks' => array_values($classRanks)]);
    }

    public function getCharacterClassSpecialties(Character $character, CharacterClassRank $characterClassRank) {
        return response()->json([
            'class_specialties' => GameClassSpecial::where('game_class_id', $characterClassRank->game_class_id)->get(),
            'specials_equipped' => $character->classSpecialsEquipped,
        ]);
    }

}
