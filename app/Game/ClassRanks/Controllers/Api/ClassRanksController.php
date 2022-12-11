<?php

namespace App\Game\ClassRanks\Controllers\Api;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\GameClassSpecial;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Http\Controllers\Controller;

class ClassRanksController extends Controller {

    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    public function __construct(UpdateCharacterAttackTypes $updateCharacterAttackTypes) {
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

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
        $classSpecialsEquipped = $character->classSpecialsEquipped()->with('gameClassSpecial')->where('equipped', '=', true)->get();

        return response()->json([
            'class_specialties' => GameClassSpecial::where('game_class_id', $characterClassRank->game_class_id)->get(),
            'specials_equipped' => $classSpecialsEquipped,
        ]);
    }

    public function equipSpecial(Character $character, GameClassSpecial $gameClassSpecial) {
        if ($character->classSpecialsEquipped->where('equipped', true)->count() >= 3) {
            return response()->json([
                'message' => 'You have the maximum amount of specials (3) equipped. You cannot equip anymore.'
            ], 422);
        }

        if ($gameClassSpecial->specialty_damage > 0) {
            if ($character->classSpecialsEquipped->where('gameClassSpecial.specialty_damage', '>', 0)->count() > 0) {
                return response()->json([
                    'message' => 'You already have a damage specialty equipped and cannot equip another one.'
                ], 422);
            }
        }

        $classSpecial = $character->classSpecialsEquipped->where('game_class_special_id', $gameClassSpecial)
                                                         ->where('character_id', $character->id)
                                                         ->where('equipped', false)
                                                         ->first();

        if (!is_null($classSpecial)) {
            $classSpecial->update([
                'equipped' => true,
            ]);
        } else {
            $character->classSpecialsEquipped()->create([
                'character_id'           => $character->id,
                'game_class_special_id'  => $gameClassSpecial->id,
                'level'                  => 1,
                'current_xp'             => 0,
                'required_xp'            => ClassSpecialValue::XP_PER_LEVEL,
                'equipped'               => true,
            ]);
        }

        $character = $character->refresh();

        $this->updateCharacterAttackTypes->updateCache($character);

        return response()->json([
            'specials_equipped' => $character->classSpecialsEquipped->where('equipped', true)->toArray(),
            'message'           => 'Equipped class special: ' . $gameClassSpecial->name
        ]);
    }

}
