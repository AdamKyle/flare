<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Item;
use App\Game\Skills\Services\DisenchantService;
use Illuminate\Support\Facades\Cache;

class DisenchantingController extends Controller {

    /**
     * @var DisenchantService $disenchantingService
     */
    private $disenchantingService;

    private $characterInventoryService;

    /**
     * Constructor
     *
     * @param DisenchantService $disenchantService
     */
    public function __construct(DisenchantService $disenchantService, CharacterInventoryService $characterInventoryService) {
        $this->disenchantingService      = $disenchantService;
        $this->characterInventoryService = $characterInventoryService;
    }

    public function disenchant(Item $item) {
        $character = auth()->user()->character;

        $inventory = Inventory::where('character_id', $character->id)->first();

        $foundItem = InventorySlot::where('equipped', false)->where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        if (is_null($foundItem)) {
            event(new ServerMessageEvent($character->user,  'Item cannot be disenchanted.'));
            return response()->json([]);
        }

        if (is_null($foundItem->item->item_suffix_id) && is_null($foundItem->item->item_prefix_id)) {
            event(new ServerMessageEvent($character->user,  'Item cannot be disenchanted.'));
            return response()->json([]);
        }

        if (!is_null($foundItem)) {
            if ($foundItem->item->type === 'quest') {
                event(new ServerMessageEvent($character->user, 'Item cannot be destroyed or does not exist. (Quest items cannot be destroyed or disenchanted)'));
                return response()->json([], 200);
            }

            $this->disenchantingService->disenchantWithSkill($character, $foundItem);

            event(new UpdateTopBarEvent($character->refresh()));
        }

        $inventory = $this->characterInventoryService->setCharacter($character->refresh());

        return response()->json([
            'message'   => 'Disenchanted item ' . $item->affix_name . ' Check server message tab for Gold Dust output.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory')
            ]
        ], 200);
    }

    public function destroy(Item $item) {
        $character = auth()->user()->character;

        $inventory = Inventory::where('character_id', $character->id)->first();

        $foundSlot = InventorySlot::where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        if (!is_null($foundSlot)) {
            if ($foundSlot->item->type === 'quest') {
                event(new ServerMessageEvent($character->user, 'Item cannot be destroyed or does not exist. (Quest items cannot be destroyed or disenchanted)'));
                return response()->json([], 200);
            }

            $name = $foundSlot->item->affix_name;

            $foundSlot->delete();

            event(new ServerMessageEvent($character->user, 'Destroyed: ' . $name));

            event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

            event(new CharacterInventoryDetailsUpdate($character->user));

            event(new UpdateTopBarEvent($character->refresh()));
        }

        return response()->json([], 200);
    }
}
