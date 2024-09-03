<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Handlers\CapitalCityBuildingManagementRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityProcessBuildingRequestHandler;

class CapitalCityBuildingManagement
{
    use ResponseBuilder;

    private array $messages = [];

    public function __construct(
        private readonly CapitalCityBuildingManagementRequestHandler $capitalCityBuildingManagementRequestHandler,
        private readonly CapitalCityProcessBuildingRequestHandler $capitalCityProcessBuildingRequestHandler) {}

    /**
     * Create the requests
     */
    public function createBuildingUpgradeRequestQueue(Character $character, Kingdom $kingdom, array $requests, string $type): array {
        return $this->capitalCityBuildingManagementRequestHandler->createRequestQueue($character, $kingdom, $requests, $type);
    }

    /**
     * Process the building request.
     *
     * - If we cannot afford the resources, then get the missing costs and send off the resource requests.
     */
    public function processBuildingRequest(CapitalCityBuildingQueue $capitalCityBuildingQueue): void {
        $this->capitalCityProcessBuildingRequestHandler->handleBuildingRequests($capitalCityBuildingQueue);
    }
}
