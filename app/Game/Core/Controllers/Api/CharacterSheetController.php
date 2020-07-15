<?php

namespace App\Game\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterSheetTransformer;

class CharacterSheetController extends Controller {

    private $manager;

    private $characterSheetTransformer;

    public function __construct(Manager $manager, CharacterSheetTransformer $characterSheetTransformer) {
        $this->middleware('auth:api');

        $this->manager                   = $manager;
        $this->characterSheetTransformer = $characterSheetTransformer;
    }

    public function sheet(Character $character) {
        $character = new Item($character, $this->characterSheetTransformer);
        $sheet     = $this->manager->createData($character)->toArray();
        
        return response()->json([
            'sheet' => $sheet,
        ], 200);
    }
}
