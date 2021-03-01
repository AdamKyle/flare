<?php

namespace App\Game\Skills\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\EnchantingValidation;
use App\Game\Skills\Services\EnchantingService;

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
        $response = $this->enchantingService->enchant($character, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}