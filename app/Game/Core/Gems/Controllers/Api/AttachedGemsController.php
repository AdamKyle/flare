<?php

namespace App\Game\Core\Gems\Controllers\Api;

use App\Flare\Models\Item;
use App\Game\Core\Gems\Services\AttachedGemService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use Illuminate\Http\JsonResponse;

class AttachedGemsController extends Controller {

    /**
     * @var AttachedGemService $attachedGemService
     */
    private AttachedGemService $attachedGemService;

    /**
     * @param AttachedGemService $attachedGemService
     */
    public function __construct(AttachedGemService $attachedGemService) {
        $this->attachedGemService = $attachedGemService;
    }

    /**
     * @param Character $character
     * @param Item $item
     * @return JsonResponse
     */
    public function getGemsFromItem(Character $character, Item $item): JsonResponse {
        $result = $this->attachedGemService->getGemsFromItem($character, $item);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
