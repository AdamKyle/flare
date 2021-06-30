<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Skills\Requests\AlchemyValidation;
use App\Game\Skills\Services\AlchemyService;
use App\Http\Controllers\Controller;

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

    public function transform(AlchemyValidation $request, Character $character) {
        $response = $this->alchemyService->transmute($character, $request->item_id);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
