<?php

namespace App\Game\ClassRanks\Controllers\Api;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\GameClassSpecial;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Http\Controllers\Controller;

class ClassRanksController extends Controller {

    private ClassRankService $classRankService;

    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;


    public function __construct(ClassRankService $classRankService, UpdateCharacterAttackTypes $updateCharacterAttackTypes) {
        $this->classRankService           = $classRankService;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    public function getCharacterClassRanks(Character $character) {

        $response = $this->classRankService->getClassRanks($character);
        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function getCharacterClassSpecialties(Character $character, CharacterClassRank $characterClassRank) {
        $classSpecialsEquipped = $character->classSpecialsEquipped()->with('gameClassSpecial')->where('equipped', '=', true)->get();

        return response()->json([
            'class_specialties' => GameClassSpecial::where('game_class_id', $characterClassRank->game_class_id)->get(),
            'specials_equipped' => array_values($classSpecialsEquipped->toArray()),
        ]);
    }

    public function equipSpecial(Character $character, GameClassSpecial $gameClassSpecial) {
        $response = $this->classRankService->equipSpecialty($character, $gameClassSpecial);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function unequipSpecial(Character $character, CharacterClassSpecialtiesEquipped $classSpecialEquipped) {
        $response = $this->classRankService->unequipSpecial($character, $classSpecialEquipped);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
