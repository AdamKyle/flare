<?php

namespace App\Game\Skills\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\AlchemyValidation;
use App\Game\Skills\Services\AlchemyService;

class AlchemyController extends Controller {

    private $alchemyService;

    public function __construct(AlchemyService $alchemyService) {
        $this->alchemyService = $alchemyService;
    }

    public function alchemyItems(Character $character) {
        return response()->json([
            'items' => $this->alchemyService->fetchAlchemistItems($character),
        ]);
    }

    public function transmute(AlchemyValidation $request, Character $character) {
        $response = $this->alchemyService->transmute($character, $request->item_to_craft);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
