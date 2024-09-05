<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\User;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Exception;
use Facades\App\Flare\Values\UserOnlineValue;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpgradeBuilding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    protected $user;

    protected KingdomBuilding $building;

    /**
     * @var int queueId
     */
    protected int $queueId;

    protected array $resourceTypes = [
        'wood', 'clay', 'stone', 'iron',
    ];

    protected ?int $capitalCityQueueId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(KingdomBuilding $building, User $user, int $queueId, ?int $capitalCityQueueId = null)
    {
        $this->user = $user;

        $this->building = $building;

        $this->queueId = $queueId;

        $this->capitalCityQueueId = $capitalCityQueueId;
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle(UpdateKingdom $updateKingdom, CapitalCityBuildingManagement $capitalCityBuildingManagement)
    {

        $queue = BuildingInQueue::find($this->queueId);

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
            UpgradeBuilding::dispatch(
                $this->building,
                $this->user,
                $this->queueId,
            )->delay($time);

            return;
            // @codeCoverageIgnoreEnd
        }

        $level = $this->building->level + 1;

        if ($this->building->gives_resources) {
            $type = $this->getResourceType();

            // @codeCoverageIgnoreStart
            if (is_null($type)) {
                $queue->delete();

                return;
            }
            // @codeCoverageIgnoreEnd

            $this->building->kingdom->{'max_'.$type} += 1000;
        }

        $this->building->kingdom->save();

        $building = $this->building->refresh();

        $building->Update([
            'level' => $level,
        ]);

        $building = $this->building->refresh();

        $building->update([
            'current_defence' => $this->building->defence,
            'current_durability' => $this->building->durability,
            'max_defence' => $this->building->defence,
            'max_durability' => $this->building->durability,
        ]);

        $building = $this->building->refresh();

        if ($building->is_farm) {
            $building->kingdom->update([
                'max_population' => $building->kingdom->max_population + (($building->level * 100) + 100),
            ]);
        }

        $characterId = $this->building->kingdom->character_id;

        $buildingInQue = BuildingInQueue::where('building_id', $this->building->id)->where('kingdom_id', $this->building->kingdom_id)->where('character_id', $characterId)->first();

        if (! is_null($buildingInQue)) {
            $buildingInQue->delete();
        }

        $updateKingdom->updateKingdom($building->kingdom->refresh());

        if (UserOnlineValue::isOnline($this->user)) {
            $kingdom = Kingdom::find($this->building->kingdom_id);
            $plane = $kingdom->gameMap->name;

            $x = $this->building->kingdom->x_position;
            $y = $this->building->kingdom->y_position;

            if ($this->user->show_building_upgrade_messages) {
                $message = $this->building->name.' finished upgrading for kingdom: '.
                    $this->building->kingdom->name.' on plane: '.$plane.
                    ' At (X/Y) '.$x.'/'.$y.' and is now level: '.$level;

                ServerMessageHandler::handleMessage($this->user, 'building_upgrade_finished', $message);
            }
        }

        if (! is_null($this->capitalCityQueueId)) {
            $capitalCityQueue = CapitalCityBuildingQueue::where('id', $this->capitalCityQueueId)->where('kingdom_id', $building->kingdom_id)->first();

            if (is_null($capitalCityQueue)) {
                throw new Exception('Capital City Queue is Null: Building Id: '.$this->capitalCityQueueId.' Kingdom Id: '.$building->kingdom_id);
            }

            $buildingRequestData = $capitalCityQueue->building_request_data;

            foreach ($buildingRequestData as $index => $requestData) {
                if ($requestData['building_id'] === $building->id) {
                    $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;
                    $buildingRequestData[$index]['messages'][] = 'Building finished upgrading. Kingdom log will be generated when all buildings for this kingdom are upgraded.';
                }
            }

            $capitalCityQueue->update([
                'building_request_data' => $buildingRequestData,
            ]);

            $capitalCityQueue = $capitalCityQueue->refresh();

            event(new UpdateCapitalCityBuildingQueueTable($capitalCityQueue->character));

            $capitalCityBuildingManagement->possiblyCreateLogForBuildingQueue($capitalCityQueue);
        }
    }

    protected function getResourceType()
    {
        foreach ($this->resourceTypes as $type) {
            if ($this->building->{'increase_in_'.$type} !== 0.0) {
                return $type;
            }
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }
}
