<?php

namespace App\Game\Core\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item as ItemCollection;
use League\Fractal\Manager;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Transformers\ShopTransformer;
use App\Game\Core\Events\BuyItemEvent;

class ShopController extends Controller {

    private $manager;

    private $shopTransformer;

    public function __construct(Manager $manager, ShopTransformer $shopTransformer) {
        $this->middleware('auth:api');

        $this->manager         = $manager;
        $this->shopTransformer = $shopTransformer;
    }

    public function index(Character $character) {
        return response()->json([
            'weapons'   => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'weapon')->get(),
            'armour'    => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->whereIn('type', [
                'body', 'leggings', 'sleeves', 'gloves', 'helmet', 'shield'
            ])->get(),

            'artifacts' => Item::with('artifactProperty')->where('type', 'artifact')->get(),
            'spells'    => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'spell')->get(),
            'rings'     => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'ring')->get(),
            'inventory' => $character->inventory->slots->filter(function($slot) use ($character) {
                return $slot->item->type !== 'quest' && is_null($character->equippedItems->where('item_id', $slot->item->id)->first());
            })->all(),
        ], 200);
    }

    public function buy(Request $request, Character $character) {
        if ($character->gold === 0) {
            return response()->json(['message' => 'You do not have enough gold.'], 422);
        }

        $item = Item::find($request->item_id);

        if (is_null($item)) {
            return response()->json(['message' => 'Item not found.'], 422);
        }

        if ($item->cost > $character->gold) {
            return response()->json(['message' => 'You do not have enough gold.'], 422);
        }

        event(new BuyItemEvent($item, $character));

        return response()->json([
            'message' => 'Purchased ' . $item->name . '.',
        ], 200);
    }

    public function sell(Request $request, Character $character) {

    }
}
