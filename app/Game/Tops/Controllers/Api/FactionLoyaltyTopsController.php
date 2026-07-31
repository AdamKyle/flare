<?php

namespace App\Game\Tops\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Tops\Services\FactionLoyaltyTopsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FactionLoyaltyTopsController extends Controller
{
    public function __construct(private readonly FactionLoyaltyTopsService $factionLoyaltyTopsService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->factionLoyaltyTopsService->leaderboard($request));
    }

    public function show(Character $character): JsonResponse
    {
        return response()->json($this->factionLoyaltyTopsService->detail($character));
    }
}
