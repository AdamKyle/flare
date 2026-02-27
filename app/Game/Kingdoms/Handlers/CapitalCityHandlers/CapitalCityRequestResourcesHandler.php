<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest as CapitalCityResourceRequestJob;
use App\Game\Kingdoms\Service\KingdomMovementTimeCalculationService;
use App\Game\Kingdoms\Service\ResourceTransferService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Support\Facades\Log;

class CapitalCityRequestResourcesHandler
{
    private array $messages = [];

    public function __construct(
        private readonly ResourceTransferService $resourceTransferService,
        private readonly KingdomMovementTimeCalculationService $kingdomMovementTimeCalculationService,
        private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
    ) {}

    /**
     * Handle the request for the resources.
     */
    public function handleResourceRequests(
        CapitalCityUnitQueue|CapitalCityBuildingQueue $queue,
        Character $character,
        array $summedMissingCosts,
        array $requestData,
        Kingdom $kingdom,
        string $type,
    ): void {
        $kingdomWhoCanAfford = $this->getKingdomWhoCanAffordCosts($character, $kingdom, $summedMissingCosts);

        if (is_null($kingdomWhoCanAfford)) {
            $requestData = $this->markRequestsAsRejected($requestData);
            $this->messages[] = 'Resource Request Rejected: No kingdom could be found to request the resources for these buildings.';

            $queue = $this->updateQueueData($queue, $requestData, CapitalCityQueueStatus::REJECTED);

            $this->logAndTriggerEvents($queue);

            Log::channel('capital_city_building_upgrades')->info('Requests were rejected because: Resource Request Rejected: No kingdom could be found to request the resources for these buildings.');

            return;
        }

        Log::channel('capital_city_building_upgrades')->info('handleResourceRequests', [
            '$kingdomWhoCanAfford' => $kingdomWhoCanAfford->id,
        ]);

        if (! $this->resourceTransferService->bothKingdomsHaveAMarketPlace($kingdom, $kingdomWhoCanAfford)) {
            $requestData = $this->markRequestsAsRejected($requestData);
            $this->messages[] = 'Resource Request Rejected: Your kingdoms: '.$kingdom->name.' and '.$kingdomWhoCanAfford->name.' both must have a Market Place at level 5 or higher.';

            $queue = $this->updateQueueData($queue, $requestData, CapitalCityQueueStatus::REJECTED);

            $this->logAndTriggerEvents($queue);

            Log::channel('capital_city_building_upgrades')->info('Requests were rejected because: Resource Request Rejected: Your kingdoms: '.$kingdom->name.' and '.$kingdomWhoCanAfford->name.' both must have a Market Place at level 5 or higher.');

            return;
        }

        if (! $this->resourceTransferService->canAffordPopulationCost($kingdomWhoCanAfford)) {
            $requestData = $this->markRequestsAsRejected($requestData);
            $this->messages[] = 'Resource Request Rejected: When asking '.$kingdomWhoCanAfford->name.' For the resources to fulfill each request, the kingdom told us they do not have the population (need 50) to send a caravan of resources.';

            $queue = $this->updateQueueData($queue, $requestData, CapitalCityQueueStatus::REJECTED);

            $this->logAndTriggerEvents($queue);

            Log::channel('capital_city_building_upgrades')->info('Requests were rejected because: Resource Request Rejected: Resource Request Rejected: When asking '.$kingdomWhoCanAfford->name.' For the resources to fulfill each request, the kingdom told us they do not have the population (need 50) to send a caravan of resources.');

            return;
        }

        if (! $this->resourceTransferService->hasRequiredSpearmen($kingdomWhoCanAfford)) {
            $requestData = $this->markRequestsAsRejected($requestData);

            $this->messages[] = 'Resource Request Rejected: When asking '.$kingdomWhoCanAfford->name.' For the resources to fulfill each request, the kingdom told us they do not have enough spearmen (need 75) to go with the caravan and guard them.';

            $queue = $this->updateQueueData($queue, $requestData, CapitalCityQueueStatus::REJECTED);

            $this->logAndTriggerEvents($queue);

            Log::channel('capital_city_building_upgrades')->info('Requests were rejected because: Resource Request Rejected: When asking '.$kingdomWhoCanAfford->name.' For the resources to fulfill each request, the kingdom told us they do not have enough spearmen (need 75) to go with the caravan and guard them.');

            return;
        }

        $queue = $this->updateQueueData($queue, $requestData, CapitalCityQueueStatus::REQUESTING);

        Log::channel('capital_city_building_upgrades')->info('Sending Resource Request', [
            '$queue' => $queue,
            '$kingdom' => $kingdom->id,
            '$kingdomWhoCanAfford' => $kingdomWhoCanAfford->id,
            '$summedMissingCosts' => $summedMissingCosts,
            '$type' => $type,
        ]);

        $this->createResourceRequest($queue, $kingdom, $kingdomWhoCanAfford, $summedMissingCosts, $type);

        $this->logAndTriggerEvents($queue);
    }

    private function updateQueueData(CapitalCityBuildingQueue|CapitalCityUnitQueue $queue, array $requestData, string $type): CapitalCityBuildingQueue|CapitalCityUnitQueue
    {

        $requestData = collect($requestData)
            ->map(function ($item) use ($type) {
                if (! in_array($item['secondary_status'], [CapitalCityQueueStatus::REJECTED, CapitalCityQueueStatus::CANCELLED])) {
                    return array_merge($item, ['secondary_status' => $type]);
                }

                return $item;
            })
            ->toArray();

        $queue->update([
            'unit_request_data' => $requestData,
            'messages' => $this->messages,
        ]);

        return $queue->refresh();
    }

    /**
     * Create and send off the resource request.
     *
     * @return void
     */
    private function createResourceRequest(
        CapitalCityUnitQueue|CapitalCityBuildingQueue $queue,
        Kingdom $requestingKingdom,
        Kingdom $requestingFromKingdom,
        array $missingResources,
        string $type
    ) {
        $character = $requestingKingdom->character;

        $timeToKingdom = $this->kingdomMovementTimeCalculationService->getTimeToKingdom($character, $requestingFromKingdom, $requestingKingdom);

        $timeTillFinished = now()->addMinutes($timeToKingdom);
        $startTime = now();

        $resourceRequest = CapitalCityResourceRequest::create([
            'kingdom_requesting_id' => $requestingKingdom->id,
            'request_from_kingdom_id' => $requestingFromKingdom->id,
            'resources' => $missingResources,
            'started_at' => $startTime,
            'completed_at' => $timeTillFinished,
        ]);

        $queue->update([
            'started_at' => $startTime,
            'completed_at' => $timeTillFinished,
            'status' => CapitalCityQueueStatus::REQUESTING,
        ]);

        $queue = $queue->refresh();

        $delayJobTime = $timeToKingdom >= 15 ? $startTime->clone()->addMinutes(15) : $timeTillFinished;
        Log::channel('capital_city_building_upgrades')->info('Dispatching Resource Requests');
        CapitalCityResourceRequestJob::dispatch($queue->id, $resourceRequest->id, $type)->delay($delayJobTime);

        $this->resourceTransferService->sendOffBasicUnitMovement($requestingKingdom, $requestingFromKingdom, $missingResources);
    }

    /**
     * Find the first kingdom who can afford the costs.
     */
    private function getKingdomWhoCanAffordCosts(Character $character, Kingdom $kingdom, array $missingCosts): ?Kingdom
    {
        return $character->kingdoms()->where('id', '!=', $kingdom->id)->where(function ($q) use ($missingCosts) {
            foreach ($missingCosts as $resource => $amount) {
                if ($resource !== 'population') {
                    $q->where('current_'.$resource, '>=', $amount);
                }
            }
        })->where('game_map_id', $kingdom->game_map_id)->first();
    }

    /**
     * Mark all requests as rejected where secondary status is REQUESTING.
     */
    private function markRequestsAsRejected(array $requestData): array
    {
        return collect($requestData)
            ->map(fn ($item) => array_merge($item, ['secondary_status' => CapitalCityQueueStatus::REJECTED]))
            ->toArray();
    }

    private function logAndTriggerEvents(CapitalCityUnitQueue|CapitalCityBuildingQueue $queue): void
    {

        if ($queue instanceof CapitalCityUnitQueue) {
            $this->capitalCityKingdomLogHandler->possiblyCreateLogForUnitQueue($queue);

            event(new UpdateCapitalCityUnitQueueTable($queue->character->refresh()));
        }

        if ($queue instanceof CapitalCityBuildingQueue) {
            $this->capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($queue);

            event(new UpdateCapitalCityBuildingQueueTable($queue->character->refresh()));
        }
    }
}
