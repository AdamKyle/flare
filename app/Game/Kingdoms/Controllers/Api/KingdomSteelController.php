<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\SteelSmeltingRequest;
use App\Game\Kingdoms\Service\SteelSmeltingService;
use App\Http\Controllers\Controller;

class KingdomSteelController extends Controller {

    /**
     * @var SteelSmeltingService $kingdomSmeltingService
     */
    private SteelSmeltingService $kingdomSmeltingService;

    /**
     * @param SteelSmeltingService $kingdomSmeltingService
     */
    public function __construct(SteelSmeltingService $kingdomSmeltingService) {
        $this->kingdomSmeltingService = $kingdomSmeltingService;
    }

    public function smeltSteel(SteelSmeltingRequest $request, Kingdom $kingdom) {
        $result = $this->kingdomSmeltingService->smeltSteel($request->amount_to_smelt, $kingdom);

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

    public function cancelSmelting(Kingdom $kingdom) {
        $result = $this->kingdomSmeltingService->cancelSmeltingEvent($kingdom);

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }

}
