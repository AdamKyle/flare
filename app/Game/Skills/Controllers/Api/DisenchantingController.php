<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Item;
use App\Game\Skills\Services\DisenchantService;

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
        } else {
            event(new ServerMessageEvent($character->user, 'Item cannot be destroyed or does not exist. (Quest items cannot be destroyed or disenchanted)'));
        }

        return response()->json([], 200);
    }

    public function destroy(Item $item) {
        $character = auth()->user()->character->refresh();

        $foundItem = $character->inventory->slots->filter(function($slot) use ($item) {
            if (!$slot->equipped && $slot->item->type !== 'quest' && $slot->item_id === $item->id) {
                return $slot;
            }
        })->first();

        if (!is_null($foundItem)) {
            $this->disenchantingService->disenchantWithOutSkill($character, $foundItem);
        } else {
            event(new ServerMessageEvent($character->user, 'Item cannot be destroyed or does not exist. (Quest items cannot be destroyed or disenchanted)'));
        }

        return response()->json([], 200);
    }
}
