<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Item;
use App\Game\Skills\Services\DisenchantService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DisenchantingController extends Controller
{
    public function __construct(private DisenchantService $disenchantService) {}

    public function disenchant(Item $item): JsonResponse
    {

        $character = auth()->user()->character;

        $slot = $character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false)
            ->where('item_id', $item->id)
            ->first();

        if (is_null($slot)) {
            response()->json(['message' => 'Item does not exist.'], 422);
        }

        $result = $this->disenchantService->setUp($character)->disenchantItem($slot);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
