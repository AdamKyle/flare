<?php

namespace App\Game\Kingdoms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\User;
use App\Flare\Models\KingdomBuilding;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Game\Kingdoms\Service\UpdateKingdom;

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
     * @return void
     */
    public function __construct(KingdomBuilding $building, User $user, int $queueId) {
        $this->user     = $user;

        $this->building = $building;

        $this->queueId  = $queueId;
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

        $this->building->update([
            'current_durability' => $this->building->max_durability,
        ]);

        $building = $this->building->refresh();
        $kingdom  = $building->kingdom;

        if ($building->morale_increase > 0) {
            $kingdom = $building->kingdom;

            $kingdom->update([
                'current_morale' => $kingdom->current_morale + $this->building->morale_increase,
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

            event(new ServerMessageEvent($this->user, 'building-repair-finished', $message));
        }
    }
}
