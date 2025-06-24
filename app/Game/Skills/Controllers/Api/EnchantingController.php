<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Requests\EnchantingValidation;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Http\Controllers\Controller;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Http\JsonResponse;

class EnchantingController extends Controller
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(private EnchantingService $enchantingService, private CraftingService $craftingService) {}

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchAffixes(Character $character): JsonResponse
    {
        return response()->json([
            'affixes' => $this->enchantingService->fetchAffixes($character, true),
            'skill_xp' => $this->enchantingService->getEnchantingXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    /**
     * @param EnchantingValidation $request
     * @param Character $character
     * @return JsonResponse
     * @throws Exception
     */
    public function enchant(EnchantingValidation $request, Character $character): JsonResponse
    {
        if (!$character->can_craft) {
            return response()->json(['message' => 'You must wait to enchant again.'], 422);
        }

        $slot = $this->enchantingService->getSlotFromInventory($character, $request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'Invalid Slot.'], 422);
        }

        if ($slot->item->type === 'quest') {
            return response()->json(['message' => 'You cannot enchant quest items.'], 422);
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $request->affix_ids, $slot->item->id);

        if ($cost > $character->gold) {
            ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::ENCHANTMENT_FAILED, 'Not enough gold to enchant that.');

            return response()->json([
                'affixes' => $this->enchantingService->fetchAffixes($character->refresh(), true, false),
                'skill_xp' => $this->enchantingService->getEnchantingXP($character),
            ]);
        }

        $timeOut = $this->enchantingService->timeForEnchanting($slot->item);

        event(new CraftedItemTimeOutEvent($character->refresh(), $timeOut));

        $this->enchantingService->enchant($character, $request->all(), $slot, $cost);

        return response()->json([
            'affixes' => $this->enchantingService->fetchAffixes($character->refresh(), true, false),
            'skill_xp' => $this->enchantingService->getEnchantingXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }
}
