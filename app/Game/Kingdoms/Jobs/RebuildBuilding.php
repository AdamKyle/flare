<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\User;
use App\Flare\Models\KingdomBuilding;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Kingdoms\Service\UpdateKingdom;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class RebuildBuilding implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User $user
     */
    protected User $user;

    /**
     * @var KingdomBuilding $building
     */
    protected KingdomBuilding $building;

    protected int|null $capitalCityBuildingQueueId = null;

    /**
     * @var int queueId
     */
    protected int $queueId;

    /**
     * Create a new job instance.
     *
     * @param KingdomBuilding $building
     * @param User $user
     * @param int $queueId
     * @param int|null $capitalCityBuildingQueueId
     */
    public function __construct(KingdomBuilding $building, User $user, int $queueId, int $capitalCityBuildingQueueId = null) {
        $this->user     = $user;

        $this->building = $building;

        $this->queueId  = $queueId;

        $this->capitalCityBuildingQueueId = $capitalCityBuildingQueueId;
    }

    /**
     * Execute the job.
     *
     * @param UpdateKingdom $updateKingdom
     * @return void
     */
    public function handle(UpdateKingdom $updateKingdom, CapitalCityBuildingManagement $capitalCityBuildingManagement): void {
        $queue = BuildingInQueue::find($this->queueId);

        if (is_null($queue)) {
            return;
        }

        $this->building->update([
            'current_durability' => $this->building->max_durability,
        ]);

        $building = $this->building->refresh();
        $kingdom  = $building->kingdom;

        if ($building->morale_increase > 0) {
            $kingdom = $building->kingdom;

            $newMorale = $kingdom->current_morale + $this->building->morale_increase;

            if ($newMorale > 1) {
                $newMorale = 1;
            }

            $kingdom->update([
                'current_morale' => $newMorale,
            ]);
        }

        $kingdom = $kingdom->refresh();

        $queue->delete();

        $updateKingdom->updateKingdom($kingdom);

        if (UserOnlineValue::isOnline($this->user)) {
            $x       = $kingdom->x_position;
            $y       = $kingdom->y_position;
            $plane   = $kingdom->gameMap->name;

            $message = $this->building->name . ' finished being rebuilt for kingdom: ' .
                $this->building->kingdom->name . ' on plane: '.$plane.' At: (X/Y) '.$x.'/'.$y.'.';

            ServerMessageHandler::handleMessage($this->user, 'building_repair_finished', $message);
        }

        if (!is_null($this->capitalCityBuildingQueueId)) {
            $capitalCityQueue = CapitalCityBuildingQueue::find($this->capitalCityBuildingQueueId);

            if (is_null($capitalCityQueue)) {
                return;
            }

            $buildingRequestData = $capitalCityQueue->building_request_data;

            foreach ($buildingRequestData as $index => $requestData) {
                if ($requestData['building_id'] === $this->building->id) {
                    $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;
                    $buildingRequestData[$index]['messages'][] = 'Building finished repairing. Kingdom log will be generated when all buildings for this kingdom are repaired.';
                }
            }

            $capitalCityQueue->update([
                'building_request_data' => $buildingRequestData
            ]);

            $capitalCityQueue = $capitalCityQueue->refresh();

            $capitalCityBuildingManagement->possiblyCreateLogForQueue($capitalCityQueue);
        }
    }
}
