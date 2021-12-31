<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Requests\PurchaseRandomEnchantment;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;

class RandomEnchantController extends Controller {

    private $randomAffixGenerator;

    private $randomEnchantmentService;

    public function __construct(RandomAffixGenerator $randomAffixGenerator,
                                RandomEnchantmentService $randomEnchantmentService
    ) {
        $this->randomAffixGenerator     = $randomAffixGenerator;
        $this->randomEnchantmentService = $randomEnchantmentService;
    }

    public function purchase(PurchaseRandomEnchantment $request, Character $character) {

        if ($character->isInventoryFull()) {
            return response()->json([
                'message' => 'Nope, your inventory is full.'
            ], 422);
        }

        if ($character->gold < $this->randomEnchantmentService->getCost($request->type)) {
            return response()->json([
                'message' => 'Nope, not enough gold.'
            ], 422);
        }

        $item = $this->randomEnchantmentService->generateForType($character, $request->type);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $item->id,
        ]);

        $character = $character->refresh();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        event(new UpdateTopBarEvent($character));

        broadcast(new ServerMessageEvent($character->user, 'The Queen of Hearts blushes, smiles and bats her eye lashes at you as she hands you, from out of no where, a new shiny object: ' . $item->affix_name));

        return response()->json([
            'item' => $item
        ], 200);
    }


}
