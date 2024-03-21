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
use Exception;
use Illuminate\Http\JsonResponse;

class ClassRanksController extends Controller {

    /**
     * @var ClassRankService $classRankService
     */
    private ClassRankService $classRankService;

    /**
     * @var UpdateCharacterAttackTypes $updateCharacterAttackTypes
     */
    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    /**
     * @param ClassRankService $classRankService
     * @param UpdateCharacterAttackTypes $updateCharacterAttackTypes
     */
    public function __construct(ClassRankService $classRankService, UpdateCharacterAttackTypes $updateCharacterAttackTypes) {
        $this->classRankService           = $classRankService;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function getCharacterClassRanks(Character $character): JsonResponse {

        $response = $this->classRankService->getClassRanks($character);
        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function getCharacterClassSpecialties(Character $character): JsonResponse {

        return response()->json($this->classRankService->getSpecials($character));
    }

    /**
     * @param Character $character
     * @param GameClassSpecial $gameClassSpecial
     * @return JsonResponse
     * @throws Exception
     */
    public function equipSpecial(Character $character, GameClassSpecial $gameClassSpecial): JsonResponse {
        $response = $this->classRankService->equipSpecialty($character, $gameClassSpecial);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param Character $character
     * @param CharacterClassSpecialtiesEquipped $classSpecialEquipped
     * @return JsonResponse
     * @throws Exception
     */
    public function unequipSpecial(Character $character, CharacterClassSpecialtiesEquipped $classSpecialEquipped): JsonResponse {

        $response = $this->classRankService->unequipSpecial($character, $classSpecialEquipped);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
