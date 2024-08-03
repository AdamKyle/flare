<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Skills\Requests\GemCraftingValidation;
use App\Game\Skills\Services\GemService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;

class GemCraftingController extends Controller
{
    private GemService $gemService;

    public function __construct(GemService $gemService)
    {
        $this->gemService = $gemService;
    }

    /**
     * @throws Exception
     */
    public function getCraftableItems(Character $character): JsonResponse
    {
        return response()->json([
            'tiers' => $this->gemService->getCraftableTiers($character),
            'skill_xp' => $this->gemService->fetchSkillXP($character),
        ]);
    }

    /**
     * @throws Exception
     */
    public function craftGem(Character $character, GemCraftingValidation $request): JsonResponse
    {

        $result = $this->gemService->generateGem($character, $request->tier);

        $status = $result['status'];
        unset($result['status']);

        $result['tiers'] = $this->gemService->getCraftableTiers($character);
        $result['skill_xp'] = $this->gemService->fetchSkillXP($character);

        return response()->json($result, $status);
    }
}
