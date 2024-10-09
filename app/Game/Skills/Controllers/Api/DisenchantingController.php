<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Character\CharacterInventory\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DisenchantingController extends Controller
{
    public function __construct(private DisenchantService $disenchantService) {}

    /**
     * @param Item $item
     * @return JsonResponse
     */
    public function disenchant(Item $item): JsonResponse
    {
        $result = $this->disenchantService->disenchantItem(auth()->user()->character, $item);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Item $item
     * @return JsonResponse
     */
    public function destroy(Item $item): JsonResponse
    {
        $character = auth()->user()->character;

        $inventory = Inventory::where('character_id', $character->id)->first();

        $foundSlot = InventorySlot::where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        if (! is_null($foundSlot)) {
            if ($foundSlot->item->type === 'quest') {
                event(new ServerMessageEvent($character->user, 'Item cannot be destroyed or does not exist. (Quest items cannot be destroyed or disenchanted)'));

                return response()->json([], 200);
            }

            $name = $foundSlot->item->affix_name;

            $foundSlot->delete();

            event(new ServerMessageEvent($character->user, 'Destroyed: ' . $name));

            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

            event(new UpdateTopBarEvent($character->refresh()));
        }

        return response()->json([], 200);
    }
}
