<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Jobs\MoveUnits;
use App\Game\Kingdoms\Values\BuildingCosts;
use App\Game\Kingdoms\Values\UnitNames;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Messages\Events\ServerMessageEvent;
use Carbon\Carbon;

class ResourceTransferService {

    use ResponseBuilder;

    const POPULATION_COST = 50;

    const SPEARMEN_COST = 75;

    const MAX_WITHOUT_AIR_SHIP = 5000;

    const MAX_WITH_AIR_SHIP = 10000;

    private array $additionalMessagesForLog = [];

    /**
     * @param DistanceCalculation $distanceCalculation
     */
    public function __construct(private readonly DistanceCalculation $distanceCalculation) {}

    /**
     * Fetch kingdoms you can transfer resources from.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return array
     */
    public function fetchKingdomsToTransferResourcesFrom(Character $character, Kingdom $kingdom): array {

        return $this->successResult([
            'kingdoms' => $this->fetchKingdomsForResourceRequests($character, $kingdom),
        ]);
    }

    /**
     * @param Character $character
     * @param array $params
     * @param int|null $capitalCityQueueId
     * @param int|null $buildingId
     * @param int|null $unitId
     * @return array
     */
    public function sendOffResourceRequest(Character $character, array $params, int $capitalCityQueueId = null, int $buildingId = null, int $unitId = null): array {

        $requestingKingdom = Kingdom::find($params['kingdom_requesting']);
        $requestingFromKingdom = Kingdom::find($params['kingdom_requesting_from']);

        if (!$this->ownsKingdom($character, $requestingKingdom, $requestingFromKingdom)) {
            return $this->errorResult('Not allowed to do that.');
        }

        if (!$this->onTheSameMapAsTheCharacter($requestingKingdom, $requestingFromKingdom)) {
            return $this->errorResult('Your kingdoms ('.$requestingKingdom->name.' and '.$requestingFromKingdom->name.') must both be on the same map.');
        }

        if (!$this->hasRequestedResourceAmount($requestingFromKingdom, $params['amount_of_resources'], $params['type_of_resource'])) {
            return $this->errorResult($requestingFromKingdom->name . ' does not have: ' . number_format($params['amount_of_resources']) . ' of type: ' . $params['type_of_resource']);
        }

        if (!$this->bothKingdomsHaveAMarketPlace($requestingKingdom, $requestingFromKingdom)) {
            return $this->errorResult('Both the requesting kingdom ('.$requestingKingdom->name.') and the kingdom ('.$requestingFromKingdom->name.') to request from must have Market Place built and upgraded to level 5.');
        }

        if (!$this->canAffordPopulationCost($requestingFromKingdom)) {
            return $this->errorResult('The kingdom: '.$requestingFromKingdom->name.' you are requesting resources from does not have enough population to move this amount of resources.');
        }

        if (!$this->hasRequiredSpearmen($requestingFromKingdom)) {
            return $this->errorResult('The kingdom: '.$requestingFromKingdom->name.' you are requesting resources from does not have enough spearmen to guard to the transportation');
        }

        $useAirShip = $params['use_air_ship'];

        if (!$this->hasAirShip($requestingFromKingdom)) {
            $useAirShip = false;
        }

        $amountOfResources = $this->getActualAmount($params['amount_of_resources'], $useAirShip);

        $this->sendOffRequestForResources($requestingKingdom, $requestingFromKingdom, $amountOfResources, $params['type_of_resource'], $useAirShip, $capitalCityQueueId, $buildingId, $unitId);

        return $this->successResult([
            'message' => 'You have requested: ' . number_format($amountOfResources) . ' of type: ' . $params['type_of_resource'] . ' from: ' .
                $requestingFromKingdom->name . ' to be transported to: ' . $requestingKingdom->name .
                '. The resources are on the way, check your queues to see the movement. A log will be delivered once the resources arrive.' .
            ' (Should the resources you request be more then whats being delivered, the log will also explain why.)',
            'kingdoms' => $this->fetchKingdomsForResourceRequests($requestingKingdom->character, $requestingKingdom),
        ]);
    }

    private function getActualAmount(int $amountOfResources, bool $useAirShip): int {

        if ($useAirShip) {
            if ($amountOfResources > self::MAX_WITH_AIR_SHIP) {

                $this->additionalMessagesForLog[] = 'Amount of requested resources: ' . $amountOfResources . ' is greater then: ' . self::MAX_WITH_AIR_SHIP . '. Reductions have been made.';

                return self::MAX_WITH_AIR_SHIP;
            }
        }

        if ($amountOfResources > self::MAX_WITHOUT_AIR_SHIP) {

            $this->additionalMessagesForLog[] = 'Amount of requested resources: ' . $amountOfResources . ' is greater then: ' . self::MAX_WITHOUT_AIR_SHIP . '. Reductions have been made.';

            return self::MAX_WITHOUT_AIR_SHIP;
        }

        return $amountOfResources;
    }

    private function fetchKingdomsForResourceRequests(Character $character, Kingdom $kingdom): array {
        $kingdoms = $character->kingdoms()->where('id', '!=', $kingdom->id)->where('game_map_id', $kingdom->game_map_id)->get();

        return $kingdoms->map(function($otherKingdom) use ($kingdom) {

            $pixelDistance = $this->distanceCalculation->calculatePixel($kingdom->x_position, $kingdom->y_position,
                $otherKingdom->x_position, $otherKingdom->y_position);

            $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

            return [
                'kingdom_name' => $otherKingdom->name,
                'kingdom_id' => $otherKingdom->id,
                'x_position' => $otherKingdom->x_position,
                'y_position' => $otherKingdom->y_position,
                'current_stone' => $otherKingdom->current_stone,
                'current_wood' => $otherKingdom->current_wood,
                'current_steel' => $otherKingdom->current_steel,
                'current_clay' => $otherKingdom->current_clay,
                'current_iron' => $otherKingdom->current_iron,
                'time_to_travel' => $timeToKingdom,
            ];
        })->toArray();
    }

    private function ownsKingdom(Character $character, Kingdom $requestingKingdom, Kingdom $requestingFromKingdom): bool {

        if ($character->id !== $requestingKingdom->character_id) {
            return false;
        }

        if ($character->id !== $requestingFromKingdom->character_id) {
            return false;
        }

        return true;
    }

    private function onTheSameMapAsTheCharacter(Kingdom $requestingKingdom, Kingdom $requestingFromKingdom): bool {
        return $requestingKingdom->game_map_id === $requestingFromKingdom->game_map_id;
    }

    private function hasRequestedResourceAmount(Kingdom $requestFromKingdom, int $amount, string $type): bool {

        if ($type === 'all') {
            return true;
        }

        $currentAmountFromKingdom = $requestFromKingdom->{$type . '_current'};

        return $amount <= $currentAmountFromKingdom;
    }

    private function canAffordPopulationCost(Kingdom $requestFromKingdom): bool {
        return $requestFromKingdom->current_population >= self::POPULATION_COST;
    }

    private function hasAirShip(Kingdom $requestFromKingdom): bool {
        return $requestFromKingdom->units->where('gameUnit.name', '=', UnitNames::AIRSHIP)->count() > 0;
    }

    private function hasRequiredSpearmen(Kingdom $requestFromKingdom): bool {

        $spearmen = $requestFromKingdom->units->where('gameUnit.name', '=', UnitNames::SPEARMEN)->first();

        if (is_null($spearmen)) {
            return false;
        }

        return $spearmen->amount >= self::SPEARMEN_COST;
    }

    private function bothKingdomsHaveAMarketPlace(Kingdom $requestingKingdom, Kingdom $requestingFromKingdom): bool {

        $requestingMarketPlace = $requestingKingdom->buildings->where('gameBuilding.name', '=', BuildingCosts::MARKET_PLACE)->where('level', '>=', 5);
        $requestingFromMarketPlace = $requestingFromKingdom->buildings->where('gameBuilding.name', '=', BuildingCosts::MARKET_PLACE)->where('level', '>=', 5);

        return !is_null($requestingMarketPlace) && !is_null($requestingFromMarketPlace);
    }

    private function sendOffRequestForResources(Kingdom $requestingKingdom, Kingdom $requestingFromKingdom, int $amount, string $type, bool $useAirShip, int $capitalCityQueueId = null, int $buildingId = null, int $unitId = null): void {

        $resources = ['wood', 'stone', 'clay', 'iron', 'steel'];

        $resourcesToRequest = [];

        if ($type === 'all') {
            foreach ($resources as $resource) {

                if ($requestingFromKingdom->{'current_' . $resource} >= $amount) {
                    $resourcesToRequest[$resource] = $amount;

                    $requestingFromKingdom->{'current_' . $resource} -= $amount;
                } else {

                    $amount = $requestingFromKingdom->{'current_' . $resource};

                    $resourcesToRequest[$resource] = $requestingFromKingdom->{'current_' . $resource};

                    $requestingFromKingdom->{'current_' . $resource} = 0;

                    $this->additionalMessagesForLog[] = 'only took: ' . number_format($amount) . ' For type: ' . $resource . ' as you do not have enough for (request amount): ' . number_format($amount);
                }
            }
        } else {
            if ($requestingFromKingdom->{'current_' . $type} >= $amount) {
                $resourcesToRequest[$type] = $amount;

                $requestingFromKingdom->{'current_' . $type} -= $amount;
            }
        }

        $requestingFromKingdom->save();

        $requestingFromKingdom = $requestingFromKingdom->refresh();

        if ($useAirShip) {
            $requestingFromKingdom->units()
                ->whereHas('gameUnit', function ($query) {
                    $query->where('name', UnitNames::AIRSHIP);
                })
                ->first()?->decrement('amount');
        }

        $requestingFromKingdom = $requestingFromKingdom->refresh();

        $pixelDistance = $this->distanceCalculation->calculatePixel($requestingKingdom->x_position, $requestingKingdom->y_position,
            $requestingFromKingdom->x_position, $requestingFromKingdom->y_position);

        $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

       $unitMovementQueue =  UnitMovementQueue::create(
            $this->buildUnitDataForMovement($requestingKingdom, $requestingFromKingdom, $timeToKingdom)
        );

        $this->sendOffEvents($requestingKingdom, $requestingFromKingdom, $unitMovementQueue, $resourcesToRequest, $capitalCityQueueId, $buildingId, $unitId);
    }

    private function buildUnitDataForMovement(Kingdom $requestingKingdom, Kingdom $requestingFromKingdom, int $completedAtMinutes): array {

        $spearmen = $requestingFromKingdom->units()->whereHas('gameUnit', function ($query) {
            $query->where('name', UnitNames::SPEARMEN);
        })->first();

        $requestingFromKingdom->units()->whereHas('gameUnit', function ($query) {
            $query->where('name', UnitNames::SPEARMEN);
        })->update([
            'amount' => $spearmen->amount - self::SPEARMEN_COST,
        ]);

        $airShip = $requestingFromKingdom->units()->whereHas('gameUnit', function ($query) {
            $query->where('name', UnitNames::AIRSHIP);
        })->first();

        $unitMovementDetails = [
            [
                'unit_id' => $spearmen->id,
                'amount' => self::SPEARMEN_COST,
            ]
        ];

        if (!is_null($airShip)) {
            $unitMovementDetails[] = [
                'unit_id' => $airShip->id,
                'amount' => 1,
            ];
        }

        return [
            'character_id' => $requestingFromKingdom->character->id,
            'from_kingdom_id' => $requestingFromKingdom->id,
            'to_kingdom_id' => $requestingKingdom->id,
            'units_moving' => $unitMovementDetails,
            'completed_at' => now()->addMinutes($completedAtMinutes),
            'started_at' => now(),
            'moving_to_x' => $requestingKingdom->x_position,
            'moving_to_y' => $requestingKingdom->y_position,
            'from_x' => $requestingFromKingdom->x_position,
            'from_y' => $requestingFromKingdom->y_position,
            'is_attacking' => false,
            'is_recalled' => false,
            'is_returning' => false,
            'is_moving' => false,
            'resources_requested' => true,
        ];
    }

    private function sendOffEvents(Kingdom $requestingKingdom, Kingdom $requestingFromKingdom, UnitMovementQueue $unitMovementQueue, array $resourcesForRequest, int $capitalCityQueueId = null, int $buildingId = null, int $unitId = null): void {

        $user = $requestingFromKingdom->character->user;

        event(new UpdateKingdomQueues($requestingKingdom));
        event(new UpdateKingdomQueues($requestingFromKingdom));

        $minutes = (new Carbon($unitMovementQueue->completed_at))->diffInMinutes($unitMovementQueue->started_at);

        MoveUnits::dispatch($unitMovementQueue->id, [
            'amount_of_resources' => $resourcesForRequest,
            'additional_log_messages' => $this->additionalMessagesForLog,
            'capital_city_queue_id' => $capitalCityQueueId,
            'building_id' => $buildingId,
            'unit_id' => $unitId
        ])->delay($minutes);

        event(new ServerMessageEvent($user, 'Your resources are on their way. The Spearmen will guard them on their travels and return should they not die along the way!'));
    }
}
