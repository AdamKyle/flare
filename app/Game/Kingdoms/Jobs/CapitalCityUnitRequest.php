<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitRecruitments;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CapitalCityUnitRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected readonly int $capitalCityQueueId, protected readonly array $totalCosts) {}

    public function handle(UnitService $unitService, CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler): void
    {

        $queueData = CapitalCityUnitQueue::find($this->capitalCityQueueId);

        if (is_null($queueData)) {
            return;
        }

        if (!$queueData->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queueData->completed_at->diffInMinutes(now());

            if ($timeLeft >= 1) {
                if ($timeLeft <= 15) {
                    $time = now()->addMinutes($timeLeft);
                } else {
                    $time = now()->addMinutes(15);
                }

                // @codeCoverageIgnoreStart
                CapitalCityUnitRequest::dispatch(
                    $this->capitalCityQueueId,
                    $this->totalCosts
                )->onConnection('long_running')->onQueue('default_long')->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $requestData = $queueData->unit_request_data;
        $kingdom = $queueData->kingdom;

        $updatedRequestData = $this->rejectInvalidOverMaxRecruitment($queueData, $requestData, $unitService);

        if (! $this->hasRecruitableRequests($updatedRequestData)) {
            $queueData->update([
                'unit_request_data' => $updatedRequestData,
                'status' => CapitalCityQueueStatus::REJECTED,
            ]);

            $capitalCityKingdomLogHandler->possiblyCreateLogForUnitQueue($queueData->refresh());

            return;
        }

        $kingdom = $this->handleCost($kingdom, $unitService, $this->sumAcceptedCosts($updatedRequestData));

        $updatedRequestData = $this->handleRecruitment($queueData, $kingdom, $updatedRequestData, $unitService);

        $queueData->update([
            'unit_request_data' => $updatedRequestData,
            'status' => CapitalCityQueueStatus::FINISHED,
        ]);

        $queueData = $queueData->refresh();

        Log::channel('capital_city_unit_recruitments')->info('Units should now be requested.');

        event(new UpdateCapitalCityUnitRecruitments($queueData->character, $queueData->requestingKingdom));
        event(new UpdateCapitalCityUnitQueueTable($queueData->character));

        $capitalCityKingdomLogHandler->possiblyCreateLogForUnitQueue($queueData);
    }

    private function handleRecruitment(CapitalCityUnitQueue $queueData, Kingdom $kingdom, array $requestData, UnitService $unitService): array
    {

        foreach ($requestData as $index => $data) {

            if (in_array($data['secondary_status'], [CapitalCityQueueStatus::REJECTED, CapitalCityQueueStatus::CANCELLED])) {
                continue;
            }

            $gameUnit = GameUnit::where('name', $data['name'])->first();

            if (! $unitService->canQueueUnits($kingdom, $gameUnit, $data['amount'], null, $queueData->id)) {
                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                continue;
            }

            $unit = $kingdom->units()->where('game_unit_id', $gameUnit->id)->first();

            if (is_null($unit)) {
                $kingdom->units()->create([
                    'kingdom_id' => $kingdom->id,
                    'game_unit_id' => $gameUnit->id,
                    'amount' => $data['amount'],
                ]);

                $kingdom = $kingdom->refresh();

                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;

                continue;
            }

            $newAmount = $unit->amount + $data['amount'];

            $unit->update([
                'amount' => $newAmount,
            ]);

            $kingdom = $kingdom->refresh();

            $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;
        }

        return $requestData;
    }

    private function rejectInvalidOverMaxRecruitment(CapitalCityUnitQueue $queueData, array $requestData, UnitService $unitService): array
    {
        $kingdom = $queueData->kingdom;
        $requestedAmountsByUnitName = [];

        foreach ($requestData as $index => $data) {
            if (in_array($data['secondary_status'], [CapitalCityQueueStatus::REJECTED, CapitalCityQueueStatus::CANCELLED])) {
                continue;
            }

            $gameUnit = GameUnit::where('name', $data['name'])->first();
            $requestedAmountsByUnitName[$data['name']] = ($requestedAmountsByUnitName[$data['name']] ?? 0) + $data['amount'];

            if (is_null($gameUnit) || ! $unitService->canQueueUnits($kingdom, $gameUnit, $requestedAmountsByUnitName[$data['name']], null, $queueData->id)) {
                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;
            }
        }

        return $requestData;
    }

    private function hasRecruitableRequests(array $requestData): bool
    {
        return collect($requestData)
            ->contains(fn($data) => ! in_array($data['secondary_status'], [CapitalCityQueueStatus::REJECTED, CapitalCityQueueStatus::CANCELLED]));
    }

    private function handleCost(Kingdom $kingdom, UnitService $unitService, array $totalCosts): Kingdom
    {
        return $unitService->updateKingdomResourcesForTotalCost($kingdom, $totalCosts);
    }

    private function sumAcceptedCosts(array $requestData): array
    {
        $costs = collect($requestData)
            ->reject(fn($request) => in_array($request['secondary_status'], [CapitalCityQueueStatus::REJECTED, CapitalCityQueueStatus::CANCELLED]))
            ->filter(fn($request) => isset($request['costs']))
            ->map(fn($request) => collect($request['costs']))
            ->reduce(fn($carry, $requestCosts) => $carry->merge($requestCosts)->map(fn($value, $key) => $carry->get($key, 0) + $value), collect())
            ->toArray();

        return empty($costs) ? $this->totalCosts : $costs;
    }
}
