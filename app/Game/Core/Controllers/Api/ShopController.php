<?php

namespace App\Game\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item as ItemCollection;
use League\Fractal\Manager;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Transformers\ShopTransformer;

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
}
