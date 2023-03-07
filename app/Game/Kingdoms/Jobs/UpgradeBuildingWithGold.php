<?php

namespace App\Game\Kingdoms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\User;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\UpdateKingdom;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Facades\App\Flare\Values\UserOnlineValue;

class UpgradeBuildingWithGold implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User $user
     */
    protected $user;

    /**
     * @var KingdomBuilding $building
     */
    protected $building;

    /**
     * @var int queueId
     */
    protected $queueId;

    /**
     * @var int $levels
     */
    protected $levels;

    /**
     * @var array $resourceType
     */
    protected $resourceTypes = [
        'wood', 'clay', 'stone', 'iron',
    ];

    /**
     * Create a new job instance.
     *
     * @param KingdomBuilding $building
     * @param User $user
     * @param int $queueId
     * @param int $levels
     */
    public function __construct(KingdomBuilding $building, User $user, int $queueId, int $levels)
    {
        $this->user     = $user;

        $this->building = $building;

        $this->queueId  = $queueId;

        $this->levels   = $levels;
    }

    /**
     * Execute the job.
     *
     * @param UpdateKingdom $updateKingdom
     * @return void
     */
    public function handle(UpdateKingdom $updateKingdom): void {

        $queue = BuildingInQueue::find($this->queueId);

        if (is_null($queue)) {
            return;
        }

        if (!$queue->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queue->completed_at->diffInMinutes(now());

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

            // @codeCoverageIgnoreStart
            UpgradeBuildingWithGold::dispatch(
                $this->building,
                $this->user,
                $this->queueId,
                $this->levels
            )->delay($time);

            return;
            // @codeCoverageIgnoreEnd
        }

        // Upgrade the building as many times as we need.
        for ($i = 1; $i <= $this->levels; $i++) {
            $this->upgradeBuilding($queue);
        }

        $characterId   = $this->building->kingdom->character_id;

        $buildingInQue = BuildingInQueue::where('building_id', $this->building->id)
                                        ->where('kingdom_id', $this->building->kingdom_id)
                                        ->where('character_id', $characterId)
                                        ->first();

        $kingdom = $buildingInQue->building->kingdom->refresh();

        if (!is_null($buildingInQue)) {
            $buildingInQue->delete();
        }

        $updateKingdom->updateKingdom($kingdom);
    }

    /**
     * Upgrade the building.
     *
     * @param BuildingInQueue $queue
     * @return void
     */
    protected function upgradeBuilding(BuildingInQueue $queue): void {
        $level = $this->building->level + 1;

        if ($this->building->gives_resources) {
            $type = $this->getResourceType();

            // @codeCoverageIgnoreStart
            if (is_null($type)) {
                $queue->delete();

                return;
            }
            // @codeCoverageIgnoreEnd

            $this->building->kingdom->{'max_' . $type} += 1000;
        }

        $this->building->kingdom->save();

        $building = $this->building->refresh();

        $building->Update([
            'level' => $level,
        ]);

        $building = $this->building->refresh();

        $building->update([
            'current_defence'    => $this->building->defence,
            'current_durability' => $this->building->durability,
            'max_defence'        => $this->building->defence,
            'max_durability'     => $this->building->durability,
        ]);

        $building = $this->building->refresh();

        if ($building->is_farm) {
            $building->kingdom->update([
                'max_population' => ($building->level * 100) + 100,
            ]);
        }

        if (UserOnlineValue::isOnline($this->user)) {
            $kingdom = Kingdom::find($this->building->kingdom_id);
            $plane   = $kingdom->gameMap->name;

            $x = $this->building->kingdom->x_position;
            $y = $this->building->kingdom->y_position;

            if ($this->user->show_building_upgrade_messages) {
                $message = $this->building->name . ' finished upgrading for kingdom: ' .
                    $this->building->kingdom->name . ' on plane: ' . $plane .
                    ' At (X/Y) ' . $x . '/' . $y . ' and is now level: ' . $level;

                ServerMessageHandler::handleMessage($this->user, 'building_upgrade_finished', $message);
            }
        }
    }

    /**
     * Get resource type.
     *
     * @return mixed
     */
    protected function getResourceType(): mixed {
        foreach($this->resourceTypes as $type) {
            if ($this->building->{'increase_in_' . $type} !== 0.0) {
                return $type;
            }
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }
}
