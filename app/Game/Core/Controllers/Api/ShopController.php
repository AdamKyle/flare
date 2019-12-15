<?php

namespace App\Game\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item as ItemCollection;
use League\Fractal\Manager;
use App\Flare\Models\Item;
use App\Flare\Transformers\ShopTransformer;

class ShopController extends Controller {

    private $manager;

    private $shopTransformer;

    public function __construct(Manager $manager, ShopTransformer $shopTransformer) {
        $this->middleware('auth:api');

        $this->manager         = $manager;
        $this->shopTransformer = $shopTransformer;
    }

    public function index() {
        return response()->json([
            'weapons'   => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'weapon')->get(),
            'armour'    => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->whereIn('type', [
                'body', 'leggings', 'sleeves', 'gloves', 'helmet', 'shield'
            ])->get(),

            'artifacts' => Item::with('artifactProperty')->where('type', 'artifact')->get(),
            'spells'    => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'spell')->get(),
            'rings'     => Item::doesntHave('itemAffixes')->doesntHave('artifactProperty')->where('type', 'ring')->get(),
        ], 200);
    }
}
