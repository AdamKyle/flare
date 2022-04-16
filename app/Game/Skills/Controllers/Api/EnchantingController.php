<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Skills\Jobs\ProcessEnchant;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\EnchantingValidation;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Messages\Events\ServerMessageEvent;

class EnchantingController extends Controller {

    /**
     * @var EnchantingService $enchantingService
     */
    private $enchantingService;

    /**
     * Constructor
     *
     * @param EnchantingService $enchantingService
     * @return void
     */
    public function __construct(EnchantingService $enchantingService) {
        $this->enchantingService = $enchantingService;
    }

    public function fetchAffixes(Character $character) {
        return response()->json($this->enchantingService->fetchAffixes($character), 200);
    }

    public function enchant(EnchantingValidation $request, Character $character) {
        if (!$character->can_craft) {
            return response()->json(['message' => 'invalid input.'], 429);
        }

        $slot = $this->enchantingService->getSlotFromInventory($character, $request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'invalid input.'], 422);
        }

        $cost = $this->enchantingService->getCostOfEnchantment($request->affix_ids, $slot->item->id);

        if ($cost > $character->gold || !$cost) {

            event(new ServerMessageEvent($character->user, 'You cannot afford to enchant this ...'));

            return response()->json([], 200);
        }

        $timeOut = $this->enchantingService->timeForEnchanting($slot->item);

        event(new CraftedItemTimeOutEvent($character->refresh(), $timeOut));

        ProcessEnchant::dispatch($character, $slot, $request->all());

        return response()->json([], 200);
    }
}
