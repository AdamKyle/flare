<?php

namespace App\Game\Gems\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Gems\Services\AttachedGemService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AttachedGemsController extends Controller
{
    private AttachedGemService $attachedGemService;

    public function __construct(AttachedGemService $attachedGemService)
    {
        $this->attachedGemService = $attachedGemService;
    }

    public function getGemsFromItem(Character $character, Item $item): JsonResponse
    {
        $result = $this->attachedGemService->getGemsFromItem($character, $item);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
