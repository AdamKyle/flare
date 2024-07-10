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
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Carbon\Carbon;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

class CapitalCityUnitManagement {

    use ResponseBuilder;

    const MAX_DAYS = 7;

    private array $messages = [];

    public function __construct(private readonly UnitService $unitService,
                                private readonly UnitMovementService $unitMovementService,
                                private readonly ResourceTransferService $resourceTransferService,
                                private readonly UpdateKingdom $updateKingdom) {}


    public function createUnitRequests(Character $character, Kingdom $kingdom, array $requestData): array {
        foreach ($requestData as $data) {

            $toKingdom     = $character->kingdoms->find($data['kingdom_id']);

            $time          = $this->unitMovementService->determineTimeRequired($character, $toKingdom, $kingdom->id, PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION);

            $minutes       = now()->addMinutes($time);

            $queueData = [
                'requested_kingdom' => $kingdom->id,
                'character_id' => $character->id,
                'kingdom_id'   => $data['kingdom_id'],
                'status' => CapitalCityQueueStatus::TRAVELING,
                'messages' => null,
                'started_at' => now(),
                'completed_at' => $minutes,
            ];

            $unitRequests = [];

            foreach($data['unit_requests'] as $unitRequest) {
                $unit = GameUnit::where('name', $unitRequest['unit_name'])->first();

                $unitRequests[] = [
                    'name' => $unitRequest['unit_name'],
                    'amount' => $unitRequest['unit_amount'],
                    'secondary_status' => null,
                    'costs' => $this->unitService->getCostsRequired($toKingdom, $unit, $unitRequest['unit_amount']),
                ];
            }

            $queueData['unit_request_data'] = $unitRequests;

            $queue = CapitalCityUnitQueue::create($queueData);

            event(new UpdateCapitalCityUnitQueueTable($character, $kingdom));

            CapitalCityUnitRequestMovement::dispatch($queue->id, $character->id)->delay($minutes);

            $this->updateKingdom->updateKingdom($kingdom);
        }

        return $this->successResult([
            'message' => 'Units requests have been queued up and sent off. If you close this modal you should now see
            a Unit Queue tab which will show you the progress of your requests. Kingdom logs will be generated
            foreach kingdom to details what was or was not recruited.'
        ]);
    }

    public function processUnitRequest(CapitalCityUnitQueue $capitalCityUnitQueue): void {
        $unitRequests = $capitalCityUnitQueue->unit_request_data;
        $character = $capitalCityUnitQueue->character;
        $kingdom = $capitalCityUnitQueue->kingdom;

        foreach ($unitRequests as $index => $unitRequest) {

            $gameUnit = GameUnit::where('name', $unitRequest['name'])->first();
            $gameBuildingRelation = GameBuildingUnit::where('game_unit_id', $gameUnit->id)->first();
            $building = $kingdom->buildings()->where('game_building_id', $gameBuildingRelation->game_building_id)->first();

            if ($building->is_locked) {
                $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                $this->messages[] = 'Building is locked in ' . $kingdom->name . '. You need to unlock the building: ' . $building->name . ' first by leveling a passive of the same name to level 1.';

                continue;
            }

            if ($building->level < $gameBuildingRelation->required_level) {
                $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                $this->messages[] = 'Building is under level in ' . $kingdom->name . '. You need to level the building: ' . $building->name . ' to level: ' . $gameBuildingRelation->required_level . ' first.';

                continue;
            }

            $amount = $this->updateAmount($kingdom, $gameUnit, $unitRequest['amount']);

            $unitRequests[$index]['amount'] = $amount;

            if (!$this->isTimeGreaterThanSevenDays($character, $kingdom, $gameUnit, $amount)) {
                $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                continue;
            }

            if ($this->canAffordPopulationCost($kingdom, $gameUnit, $amount)) {

                $this->messages[] = 'Cannot afford to purchase, through ' . $kingdom->name . ' treasury, the additional amount of population you need to recruit this many units.';

                $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                continue;
            }

            $currentIndex = $index;

            if (ResourceValidation::shouldRedirectUnits($gameUnit, $kingdom, $amount)) {
                $missingCosts = ResourceValidation::getMissingResources($gameUnit, $kingdom, $amount);

                foreach ($missingCosts as $resourceName => $amount) {
                    $result = $this->sendOffResourceRequest($character, $kingdom, $resourceName, $amount, $capitalCityUnitQueue->id, $gameUnit->id);

                    if (!$result) {
                        $unitRequests[$currentIndex]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                        break;
                    }

                    $unitRequests[$currentIndex]['secondary_status'] = CapitalCityQueueStatus::REQUESTING;
                }
            }

            /**
             * At this stage we are basically ready to go recruit.
             */
            if (is_null($unitRequests[$index]['secondary_status'])) {
                $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::RECRUITING;
            }
        }

        $capitalCityUnitQueue->update([
            'unit_request_data' => $unitRequests,
        ]);

        $capitalCityUnitQueue = $capitalCityUnitQueue->refresh();

        $capitalCityUnitQueue = $this->recruitUnits($capitalCityUnitQueue);

        event(new UpdateCapitalCityUnitQueueTable($character));

        $this->possiblyCreateKingdomLog($capitalCityUnitQueue);

    }

    public function recruitUnits(CapitalCityUnitQueue $capitalCityUnitQueue): CapitalCityUnitQueue {
        $unitRequests = $capitalCityUnitQueue->unit_request_data;
        $kingdom = $capitalCityUnitQueue->kingdom;

        foreach ($unitRequests as $index => $unitRequest) {

            if ($unitRequest['secondary_status'] === CapitalCityQueueStatus::RECRUITING) {

                $gameUnit = GameUnit::where('name', $unitRequest['name'])->first();

                $purchased = $this->purchasePeopleForRecruitment($kingdom, $gameUnit, $unitRequest['amount']);

                if (!$purchased) {
                    $this->messages[] = 'Cannot recruit: ' . number_format($unitRequest['amount']) . ' units from ' . $kingdom->name . ' as you do not have have enough treasury to purchase the needed population.';

                    $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                    continue;
                }

                $this->unitService->handlePayment($gameUnit, $kingdom, $unitRequest['amount']);

                $this->unitService->recruitUnits($kingdom, $gameUnit, $unitRequest['amount'], $capitalCityUnitQueue->id);
            }
        }

        $capitalCityUnitQueue->update([
            'unit_request_data' => $unitRequests,
            'messages' => $this->messages,
        ]);

        return $capitalCityUnitQueue->refresh();
    }

    public function possiblyCreateKingdomLog(CapitalCityUnitQueue $capitalCityUnitQueue): void {
        $requestData = $capitalCityUnitQueue->unit_request_data;
        $kingdom = $capitalCityUnitQueue->kingdom;
        $character = $capitalCityUnitQueue->character;

        $unitData = [];

        foreach($requestData as $data) {
            if ($data['secondary_status'] === CapitalCityQueueStatus::REJECTED ||
                $data['secondary_status'] === CapitalCityQueueStatus::FINISHED
            ) {

                $unitData[] = [
                    'unit_name' => $data['name'],
                    'amount_requested' => $data['amount'],
                    'status' => $data['secondary_status'],
                ];
            }
        }

        if (count($unitData) === count($requestData)) {
            KingdomLog::create([
                'character_id' => $character->id,
                'from_kingdom_id' => $capitalCityUnitQueue->requested_kingdom,
                'to_kingdom_id' => $kingdom->id,
                'opened' => false,
                'additional_details' => [
                    'messages' => $capitalCityUnitQueue->messages,
                    'unit_data' => $unitData,
                ],
                'status' => KingdomLogStatusValue::CAPITAL_CITY_UNIT_REQUEST,
                'published' => true,
            ]);

            $this->updateKingdom->updateKingdomLogs($kingdom->character, true);

            $capitalCityUnitQueue->delete();

            event(new UpdateCapitalCityUnitQueueTable($character));
        }
    }

    private function purchasePeopleForRecruitment(Kingdom $kingdom, GameUnit $gameUnit, int $amount): bool {
        $populationCost = $amount * (new UnitCosts(UnitCosts::PERSON))->fetchCost();

        if ($kingdom->treasury <= 0) {
            return false;
        }

        $newTreasury = $kingdom->treasury - $populationCost;

        $kingdom->update([
            'treasury' => $newTreasury > 0 ? $newTreasury : 0,
        ]);

        return true;
    }

    private function sendOffResourceRequest(Character $character, Kingdom $kingdom, string $resourceName, int $resourceAmount, int $queueId, int $unitId): bool {
        $kingdom = $character->kingdoms()
            ->where('game_map_id', $kingdom->game_map_id)
            ->where('id', '!=', $kingdom->id)
            ->where('current_' . $resourceName, '>=', $resourceAmount)
            ->first();

        if (is_null($kingdom)) {

            $this->messages[] = 'No kingdom found to request the amount '.number_format($resourceAmount). ' of ' . $resourceName . '.';

            return false;
        }

        $result = $this->resourceTransferService->sendOffResourceRequest($character, [
            'kingdom_requesting' => $kingdom->id,
            'kingdom_requesting_from' => $kingdom->id,
            'amount_of_resources' => $resourceAmount,
            'use_air_ship' => true,
            'type_of_resource' => $resourceName,
        ], $queueId, null, $unitId);

        if ($result['status'] !== 200) {

            $this->messages[] = $result['message'];

            return false;
        }

        return true;
    }

    private function updateAmount(Kingdom $kingdom, GameUnit $gameUnit, int $amount): int {

        $unit = $kingdom->units()->where('game_unit_id', $gameUnit)->first();

        if (is_null($unit)) {
            return min($amount, KingdomMaxValue::MAX_UNIT);
        }

        $newAmount = $unit->amount + $amount;

        if ($newAmount > KingdomMaxValue::MAX_UNIT) {
            $reduceAmount = $newAmount - KingdomMaxValue::MAX_UNIT;

            $this->messages[] = 'The amount of ' . $gameUnit->name . ' ('.number_format($amount).') has been reduced by: ' . number_format($reduceAmount) . ' due to being close to max amount allowed for this unit.';

            return $amount - $reduceAmount;
        }

        return $amount;
    }

    private function isTimeGreaterThanSevenDays(Character $character, Kingdom $kingdom, GameUnit $gameUnit, int $amount): bool {
        $timeTillDone = $this->getTimeForRecruitment($character, $gameUnit, $amount);

        if (now()->diffInDays($timeTillDone) > self::MAX_DAYS) {
            $this->messages[] = $gameUnit->name . ' for kingdom: ' . $kingdom->name . ' would take longer then 7 (Real World) Days. The kingdom has rejected this recruitment order. If you want this amount of units, you must recruit it from the kingdom it\'s self.';

            return false;
        }

        return true;
    }

    private function canAffordPopulationCost(Kingdom $kingdom, GameUnit $gameUnit, int $amount): bool {

        $treasury = $kingdom->treasury;
        $populationRequired = $amount * $gameUnit->required_population;
        $populationToPurchase = $this->getPopulationNeededToPurchase($kingdom, $populationRequired);

        if ($populationRequired > 0) {
            $cost = $populationToPurchase * (new UnitCosts(UnitCosts::PERSON))->fetchCost();

            return $cost > $treasury;
        }

        return true;
    }

    private function getPopulationNeededToPurchase(Kingdom $kingdom, int $populationRequired): int {
        if ($populationRequired > $kingdom->current_population) {
            return $populationRequired - $kingdom->current_population;
        }

        return 0;
    }

    private function getTimeForRecruitment(Character $character, GameUnit $gameUnit, int $amount): Carbon {
        $totalTime        = $gameUnit->time_to_recruit * $amount;
        $totalTime        = $totalTime - $totalTime * $this->unitService->fetchTimeReduction($character)->unit_time_reduction;

        return now()->addSeconds($totalTime);
    }
}
