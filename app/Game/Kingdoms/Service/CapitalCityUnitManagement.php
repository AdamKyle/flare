<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessUnitRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityUnitManagementRequestHandler;

class CapitalCityUnitManagement
{
    use ResponseBuilder;

    public function __construct(
        private readonly CapitalCityUnitManagementRequestHandler $capitalCityUnitManagementRequestHandler,
        private readonly CapitalCityProcessUnitRequestHandler $capitalCityProcessUnitRequestHandler,
    ) {}

    public function createUnitRequests(Character $character, Kingdom $kingdom, array $requestData): array
    {
        return $this->capitalCityUnitManagementRequestHandler->createUnitRequests($character, $kingdom, $requestData);
    }

    public function processUnitRequest(CapitalCityUnitQueue $capitalCityUnitQueue): void
    {
        $this->capitalCityProcessUnitRequestHandler->handleUnitRequests($capitalCityUnitQueue);
    }
}
