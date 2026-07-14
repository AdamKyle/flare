<?php

namespace App\Game\Tops\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Tops\Services\CharacterTopsInspectionService;
use App\Game\Tops\Services\CharacterTopsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterTopsController extends Controller
{
    public function __construct(
        private readonly CharacterTopsService $characterTopsService,
        private readonly CharacterTopsInspectionService $characterTopsInspectionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->characterTopsService->leaderboard($request));
    }

    public function overview(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->overview($character));
    }

    public function profile(Request $request, Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->fullProfile($character, $request->user()));
    }

    public function stats(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->stats($character));
    }

    public function equipment(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->equipment($character));
    }

    public function skills(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->skills($character));
    }

    public function factions(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->factions($character));
    }

    public function reincarnation(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->reincarnation($character));
    }

    public function activity(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->activity($character));
    }

    public function quests(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->quests($character));
    }

    public function kingdoms(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->kingdoms($character));
    }

    public function analytics(Character $character): JsonResponse
    {
        return response()->json($this->characterTopsInspectionService->analytics($character));
    }
}
