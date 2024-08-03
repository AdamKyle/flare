<?php

namespace App\Game\ClassRanks\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\GameClassSpecial;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;

class ClassRanksController extends Controller
{
    private ClassRankService $classRankService;

    private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes;

    public function __construct(ClassRankService $classRankService, UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes)
    {
        $this->classRankService = $classRankService;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
    }

    public function getCharacterClassRanks(Character $character): JsonResponse
    {

        $response = $this->classRankService->getClassRanks($character);
        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function getCharacterClassSpecialties(Character $character): JsonResponse
    {

        return response()->json($this->classRankService->getSpecials($character));
    }

    /**
     * @throws Exception
     */
    public function equipSpecial(Character $character, GameClassSpecial $gameClassSpecial): JsonResponse
    {
        $response = $this->classRankService->equipSpecialty($character, $gameClassSpecial);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @throws Exception
     */
    public function unequipSpecial(Character $character, CharacterClassSpecialtiesEquipped $classSpecialEquipped): JsonResponse
    {

        $response = $this->classRankService->unequipSpecial($character, $classSpecialEquipped);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
