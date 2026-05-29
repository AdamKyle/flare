<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Messages\Types\KingdomMessageTypes;
use Facades\App\Flare\Values\UserOnlineValue;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecruitUnits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly GameUnit $unit,
        private readonly Kingdom $kingdom,
        private readonly int $amount,
        private readonly int $queueId,
        private readonly ?int $capitalCityQueueId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(UpdateKingdom $updateKingdom, CapitalCityUnitManagement $capitalCityUnitManagement, UnitService $unitService): void
    {

        $queue = UnitInQueue::find($this->queueId);

        if (is_null($queue)) {
            return;
        }

        if (! $queue->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queue->completed_at->diffInMinutes(now());

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

            // @codeCoverageIgnoreStart
            RecruitUnits::dispatch(
                $this->unit,
                $this->kingdom,
                $this->amount,
                $this->queueId,
                $this->capitalCityQueueId,
            )->delay($time);

            return;
            // @codeCoverageIgnoreEnd
        }

        $kingdom = $queue->kingdom;

        if (! $unitService->canQueueUnits($kingdom, $this->unit, $queue->amount, $queue->id)) {
            $unitService->refundUnitRecruitment($kingdom, $this->unit, $queue->amount);
            $queue->delete();

            if (! is_null($this->capitalCityQueueId)) {
                $capitalCityQueue = CapitalCityUnitQueue::find($this->capitalCityQueueId);

                if (! is_null($capitalCityQueue)) {
                    $unitRequests = $capitalCityQueue->unit_request_data;

                    foreach ($unitRequests as $index => $request) {
                        if ($request['name'] === $this->unit->name) {
                            $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                        }
                    }

                    $capitalCityQueue->update(['unit_request_data' => $unitRequests]);
                }
            }

            return;
        }

        $amount = $this->amount;

        if ($kingdom->units->isEmpty()) {
            $kingdom->units()->create([
                'kingdom_id' => $kingdom->id,
                'game_unit_id' => $this->unit->id,
                'amount' => $amount,
            ]);
        } else {
            $found = $kingdom->units()->where('game_unit_id', $this->unit->id)->first();

            if (is_null($found)) {
                $kingdom->units()->create([
                    'kingdom_id' => $kingdom->id,
                    'game_unit_id' => $this->unit->id,
                    'amount' => $amount,
                ]);
            } else {
                $amount += $found->amount;

                $found->update([
                    'amount' => $amount,
                ]);
            }
        }

        $queue->delete();

        $kingdom = $kingdom->refresh();

        $updateKingdom->updateKingdom($kingdom);

        $x = $kingdom->x_position;
        $y = $kingdom->y_position;
        $user = $kingdom->character->user;
        $plane = $kingdom->gameMap->name;

        if (UserOnlineValue::isOnline($user)) {

            if ($user->show_unit_recruitment_messages) {
                $message = $this->unit->name.' finished recruiting for kingdom: '.
                    $this->kingdom->name.' on plane: '.$plane.' at: (X/Y) '.$x.'/'.$y.
                    '. You have a total of: '.number_format($amount);

                ServerMessageHandler::handleMessage($user, KingdomMessageTypes::UNIT_RECRUITMENT_FINISHED, $message);
            }
        }

        if (! is_null($this->capitalCityQueueId)) {
            $capitalCityQueue = CapitalCityUnitQueue::find($this->capitalCityQueueId);

            $unitRequests = $capitalCityQueue->unit_request_data;

            foreach ($unitRequests as $index => $request) {
                if ($request['name'] === $this->unit->name) {
                    $unitRequests[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;
                }
            }

            $capitalCityQueue->update([
                'unit_request_data' => $unitRequests,
            ]);

            $capitalCityQueue = $capitalCityQueue->refresh();

            $capitalCityUnitManagement->possiblyCreateKingdomLog($capitalCityQueue);
        }
    }
}
