<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Skills\Requests\GemCraftingValidation;
use App\Game\Skills\Services\GemService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use Exception;
use Illuminate\Http\JsonResponse;

class GemCraftingController extends Controller {

    /**
     * @var GemService $gemService
     */
    private GemService $gemService;

    /**
     * @param GemService $gemService
     */
    public function __construct(GemService $gemService) {
        $this->gemService = $gemService;
    }

    /**
     * @param Character $character
     * @return JsonResponse
     * @throws Exception
     */
    public function getCraftableItems(Character $character): JsonResponse {
        return response()->json($this->gemService->getCraftableTiers($character));
    }

    /**
     *
     * @param Character $character
     * @param GemCraftingValidation $request
     * @return JsonResponse
     * @throws Exception
     */
    public function craftGem(Character $character, GemCraftingValidation $request): JsonResponse {

        $result = $this->gemService->generateGem($character, $request->tier);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
