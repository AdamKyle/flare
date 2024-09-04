<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Handlers\CapitalCityProcessUnitRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityUnitManagementRequestHandler;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Carbon\Carbon;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

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

    public function processUnitRequest(CapitalCityUnitQueue $capitalCityUnitQueue): void {
        $this->capitalCityProcessUnitRequestHandler->handleUnitRequests($capitalCityUnitQueue);
    }
}
