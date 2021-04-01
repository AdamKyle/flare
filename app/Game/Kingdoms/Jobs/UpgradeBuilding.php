<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Mail\GenericMail;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\BuildingInQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;;
use App\Flare\Models\User;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Mail\UpgradedBuilding;
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
     * @var KingdomBuilding $building
     */
    protected $building;

    /**
     * @var int queueId
     */
    protected $queueId;

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
     * @return void
     */
    public function __construct(KingdomBuilding $building, User $user, int $queueId)
    {
        $this->user     = $user;

        $this->building = $building;

        $this->queueId  = $queueId;
    }

    /**
     * Execute the job.
     *
     * @param Manager $manager
     * @param KingdomTransformer $kingdomTransformer
     * @return void
     */
    public function handle(Manager $manager, KingdomTransformer $kingdomTransformer)
    {

        $queue = BuildingInQueue::find($this->queueId);

        if (is_null($queue)) {
            return;
        }

        $level = $this->building->level + 1;

        if ($this->building->gives_resources) {
            $type = $this->getResourceType();
            
            if (is_null($type)) {
                $queue->delete();

                return;
            }

            $this->building->kingdom->{'max_' . $type} += 1000;
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

        $building = $this->building->refresh();

        if ($building->is_farm) {
            $building->kingdom->update([
                'max_population' => $building->population_increase
            ]);
        }

        BuildingInQueue::where('to_level', $level)->where('building_id', $this->building->id)->where('kingdom_id', $this->building->kingdom_id)->first()->delete();
        
        if (UserOnlineValue::isOnline($this->user)) {
            $kingdom = Kingdom::find($this->building->kingdom_id);
            $kingdom = new Item($kingdom, $kingdomTransformer);
            $kingdom = $manager->createData($kingdom)->toArray();

            event(new UpdateKingdom($this->user, $kingdom));
            event(new ServerMessageEvent($this->user, 'building-upgrade-finished', $this->building->name . ' finished upgrading for kingdom: ' . $this->building->kingdom->name . ' and is now level: ' . $level));
        } else if ($this->user->upgraded_building_email) {
            Mail::to($this->user)->send(new UpgradedBuilding(
                $this->user,
                $this->building
            ));
        }
    }

    protected function getResourceType() {
        foreach($this->resourceTypes as $type) {
            if ($this->building->{'increase_in_' . $type} !== 0.0) {
                return $type;
            }
        }

        return null;
    }
}
