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
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;
use Facades\App\Flare\Values\UserOnlineValue;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
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
        'wood', 'clay', 'stone', 'iron',
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
    public function handle(Manager $manager, KingdomTransformer $kingdomTransformer)
    {

        $level = $this->building->level + 1;

        if ($this->building->gives_resources) {
            $type = $this->getResourceType();

            $this->building->kingdom->{'max_' . $type} += 1000;
        } else if ($this->building->is_farm) {
            $this->building->kingdom->max_population *= $level;
        }

        $this->building->kingdom->save();

        $this->building->refresh()->Update([
            'level' => $level,
        ]);

        $this->building->refresh()->update([
            'current_defence'    => $this->building->defence,
            'current_durability' => $this->building->durability,
            'max_defence'        => $this->building->defence,
            'max_durability'     => $this->building->durability,
        ]);

        BuildingInQueue::where('to_level', $level)->where('building_id', $this->building->id)->where('kingdom_id', $this->building->kingdoms_id)->first()->delete();
        
        if (UserOnlineValue::isOnline($this->user)) {
            $kingdom = Kingdom::find($this->building->kingdoms_id);
            $kingdom = new Item($kingdom, $kingdomTransformer);
            $kingdom = $manager->createData($kingdom)->toArray();

            event(new UpdateKingdom($this->user, $kingdom));
            event(new ServerMessageEvent($this->user, 'building-upgrade-finished', $this->building->name . ' finished upgrading for kingdom: ' . $this->building->kingdom->name . ' and is now level: ' . $level));
        } else {
            Mail::to($this->user)->send(new GenericMail(
                $this->user,
                $this->building->name . ' finished upgrading for kingdom: ' . $this->building->kingdom->name . ' and is now level: ' . $level,
                'Building Upgrade Finished',
            ));
        }
    }

    protected function getResourceType() {
        foreach($this->resourceTypes as $type) {
            dump($this->building->name, $this->building->{'increase_in_' . $type});
            if ($this->building->{'increase_in_' . $type} !== 0.0) {
                dump($type);
                return $type;
            }
        }

        return null;
    }
}
