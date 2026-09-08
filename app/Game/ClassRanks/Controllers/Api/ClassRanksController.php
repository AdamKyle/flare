<?php

namespace App\Game\ClassRanks\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\GameClassSpecial;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;

class ClassRanksController extends Controller
{
    public function __construct(
        private readonly ClassRankService $classRankService,
        private readonly AutomationRestrictionService $automationRestrictionService,
    ) {}

    /**
     * Return the Player Class Rank contract for the character.
     */
    public function getCharacterClassRanks(Character $character): JsonResponse
    {
        $response = $this->classRankService->getClassRanks($character);
        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * Return the Player Class Specialty contract for the character.
     */
    public function getCharacterClassSpecialties(Character $character): JsonResponse
    {
        return response()->json($this->classRankService->getSpecials($character));
    }

    /**
     * Equip a Class Specialty for the character.
     *
     * @throws Exception
     */
    public function equipSpecial(Character $character, GameClassSpecial $gameClassSpecial): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $response = $this->classRankService->equipSpecialty($character, $gameClassSpecial);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * Unequip a Class Specialty for the character.
     *
     * @throws Exception
     */
    public function unequipSpecial(Character $character, CharacterClassSpecialtiesEquipped $classSpecialEquipped): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $response = $this->classRankService->unequipSpecial($character, $classSpecialEquipped);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * Swap an equipped Class Specialty for a target one for the character.
     *
     * @throws Exception
     */
    public function swapSpecial(Character $character, GameClassSpecial $gameClassSpecial, CharacterClassSpecialtiesEquipped $classSpecialEquipped): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $response = $this->classRankService->swapSpecialty($character, $gameClassSpecial, $classSpecialEquipped);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * Build the blocked-automation JSON response for the character, or null when not restricted.
     */
    private function automationRestrictionJsonResponse(Character $character): ?JsonResponse
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::CLASS_RANKS);

        if (is_null($restriction)) {
            return null;
        }

        return response()->json(['message' => $restriction['message']], 422);
    }
}
