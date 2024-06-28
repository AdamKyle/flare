<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
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

            $toKingdom = $character->kingdoms->find($data['kingdom_id']);

            $time          = $this->unitMovementService->determineTimeRequired($character, $toKingdom, $kingdom->id);

            $minutes       = now()->addMinutes($time);

            $unit = GameUnit::where('name', $data['name'])->first();

            $queueData = [
                'requested_kingdom' => $kingdom->id,
                'character_id' => $character->id,
                'kingdom_id'   => $data['kingdom_id'],
                'unit_request_data' => [],
                'status' => CapitalCityQueueStatus::TRAVELING,
                'messages' => null,
                'started_at' => now(),
                'completed_at' => $minutes,
            ];

            $unitRequests = [];

            foreach($data['unit_requests'] as $unitRequest) {
                $unitRequests[] = [
                    'name' => $unitRequest['unit_name'],
                    'amount' => $unitRequest['amount'],
                    'secondary_status' => null,
                    'costs' => $this->unitService->getCostsRequired($toKingdom, $unit, $unitRequest['amount']),
                ];
            }

            $queueData['unit_request_data'][] = $unitRequests;

            $queueData = CapitalCityUnitQueue::create($queueData);

            $this->updateKingdom->updateKingdom($kingdom);
        }

        return $this->successResult([
            'messages' => 'Units requests have been queued up and sent off. If you close this modal you should now see
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

            if (ResourceValidation::shouldRedirectUnits($gameUnit, $kingdom, $amount)) {
                $missingCosts = ResourceValidation::getMissingResources($gameUnit, $kingdom, $amount);

                foreach ($missingCosts as $resourceName => $amount) {
                    $result = $this->sendOffResourceRequest($character, $kingdom, $resourceName, $amount, $capitalCityUnitQueue->id, $gameUnit->id);

                    if (!$result) {
                        $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                        break;
                    }

                    $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::RECRUITING;
                }
            }
        }

        $capitalCityUnitQueue->update([
            'unit_request_data' => $unitRequests,
        ]);

        $this->recruitUnits($capitalCityUnitQueue->refresh());
    }

    public function recruitUnits(CapitalCityUnitQueue $capitalCityUnitQueue) {

    }

    private function sendOffResourceRequest(Character $character, Kingdom $kingdom, string $resourceName, int $resourceAmount, int $queueId, int $unitId): bool {
        $kingdom = $character->kingdoms()->where('id', '!=', $kingdom->id)->where('current_' . $resourceName, '>=', $resourceAmount)->first();

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

    private function getTimeForRecruitment(Character $character, Kingdom $kingdom, GameUnit $gameUnit, int $amount): Carbon {
        $totalTime        = $gameUnit->time_to_recruit * $amount;
        $totalTime        = $totalTime - $totalTime * $this->unitService->fetchTimeReduction($character)->unit_time_reduction;

        return now()->addSeconds($totalTime);
    }
}
