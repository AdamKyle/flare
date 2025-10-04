<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitRecruitments;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
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

        if (! $queueData->completed_at->lessThanOrEqualTo(now())) {
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
                )->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $kingdom = $this->handleCost($queueData->kingdom, $unitService, $this->totalCosts);

        $requestData = $queueData->unit_request_data;

        $updatedRequestData = $this->handleRecruitment($kingdom, $requestData);

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

    private function handleRecruitment(Kingdom $kingdom, array $requestData): array
    {

        foreach ($requestData as $index => $data) {

            if (in_array($data['secondary_status'], [CapitalCityQueueStatus::REJECTED, CapitalCityQueueStatus::CANCELLED])) {
                continue;
            }

            $gameUnit = GameUnit::where('name', $data['name'])->first();

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

            if ($newAmount > KingdomMaxValue::MAX_UNIT) {
                $newAmount = KingdomMaxValue::MAX_UNIT;
            }

            $unit->update([
                'amount' => $newAmount,
            ]);

            $kingdom = $kingdom->refresh();

            $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;
        }

        return $requestData;
    }

    private function handleCost(Kingdom $kingdom, UnitService $unitService, array $totalCosts): Kingdom
    {
        return $unitService->updateKingdomResourcesForTotalCost($kingdom, $totalCosts);
    }
}
