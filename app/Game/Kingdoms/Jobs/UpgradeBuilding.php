<?php

namespace App\Game\Kingdoms\Jobs;

use App\Admin\Mail\GenericMail;
use App\Flare\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;;
use App\Flare\Models\User;
use App\Flare\Models\Building;
use App\Flare\Models\BuildingInQueue;
use App\Game\Kingdoms\Events\UpdateBuildingQueue;
use Facades\App\Flare\Values\UserOnlineValue;
use Mail;

class UpgradeBuilding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User $user
     */
    protected $user;

    /**
     * @var Building $building
     */
    protected $building;

    protected $resourceTypes = [
        'wood', 'clay', 'stone', 'iron', 'population'
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Building $building, User $user)
    {
        $this->user     = $user;

        $this->building = $building;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $level = $this->building->level + 1;

        if ($this->building->gives_resources) {
            $type = $this->getResourceType();

            $this->building->kingdom->{'current_' . $type} = 2000 * $level;
            $this->building->kingdom->{'max_' . $type}     = 2000 * $level;

            $this->building->kingdom->save();
        }

        $this->building->Update([
            'level'              => $level,
            'current_defence'    => $this->building->durability,
            'current_durability' => $this->building->defence,
            'max_defence'        => $this->building->defence,
            'max_durability'     => $this->building->durability,
        ]);

        BuildingInQueue::where('to_level', $level)->where('building_id', $this->building->id)->where('kingdom_id', $this->building->kingdom_id)->first()->delete();
        
        if (UserOnlineValue::isOnline($this->user)) {
            event(new UpdateBuildingQueue($this->user, BuildingInQueue::where('kingdom_id', $this->building->kingdom_id)->get()));
            event(new ServerMessageEvent($this->user, 'building-upgrade-finished', $this->building->name . ' Finished upgrading for kingdom: ' . $this->building->kingdom->name . ' and is now level: ' . $level));
        } else {
            Mail::to($this->user)->send(new GenericMail(
                $this->user,
                $this->building->name . ' Finished upgrading for kingdom: ' . $this->building->kingdom->name . ' and is now level: ' . $level,
                'Building Upgrade Finished',
            ));
        }
    }

    protected function getResourceType() {
        foreach($this->resourceTypes as $type) {
            if (!is_null($this->building->{$type . '_increase'})) {
                return $type;
            }
        }
    }
}
