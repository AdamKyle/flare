<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\ResourceRequest;
use App\Game\Kingdoms\Service\ResourceTransferService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ResourceTransferController extends Controller {

    public function __construct(private readonly ResourceTransferService $resourceTransferService){}

    public function getKingdomsForResourceTransferRequest(Kingdom $kingdom, Character $character): JsonResponse {
        $response = $this->resourceTransferService->fetchKingdomsToTransferResourcesFrom($character, $kingdom);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    public function transferResources(Character $character, ResourceRequest $request): JsonResponse {
        $response = $this->resourceTransferService->sendOffResourceRequest($character, $request->all());

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

}
