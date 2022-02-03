<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
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

    /**
     * Constructor
     *
     * @param DisenchantService $disenchantService
     */
    public function __construct(DisenchantService $disenchantService) {
        $this->disenchantingService = $disenchantService;
    }

    public function disenchant(Item $item) {
        $character = auth()->user()->character->refresh();

        $foundItem = $character->inventory->slots->filter(function($slot) use ($item) {
            if (!$slot->equipped && $slot->item->type !== 'quest' && $slot->item_id === $item->id) {
                return $slot;
            }
        })->first();

        if (!is_null($foundItem)) {
            $this->disenchantingService->disenchantWithSkill($character, $foundItem);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            event(new CharacterInventoryDetailsUpdate($character->user));

            event(new UpdateTopBarEvent($character->refresh()));
        } else {
            event(new ServerMessageEvent($character->user, 'Item cannot be destroyed or does not exist. (Quest items cannot be destroyed or disenchanted)'));
        }

        return response()->json([], 200);
    }

    public function destroy(Item $item) {
        $character = auth()->user()->character->refresh();

        $foundSlot = $character->inventory->slots()->where('item_id', $item->id)->first();

        if (!is_null($foundSlot)) {
            $name = $foundSlot->item->affix_name;

            $foundSlot->delete();

            event(new ServerMessageEvent($character->user, 'Destroyed: ' . $name));

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            event(new CharacterInventoryDetailsUpdate($character->user));

            event(new UpdateTopBarEvent($character->refresh()));
        } else {
            event(new ServerMessageEvent($character->user, 'Item cannot be destroyed or does not exist. (Quest items cannot be destroyed or disenchanted)'));
        }

        return response()->json([], 200);
    }
}
