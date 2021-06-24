<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Jobs\SendOffEmail;
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

        $characterId = $this->building->kingdom->character_id;

        $buildingInQue = BuildingInQueue::where('building_id', $this->building->id)->where('kingdom_id', $this->building->kingdom_id)->where('character_id', $characterId)->first();

        if (!is_null($buildingInQue)) {
            $buildingInQue->delete();
        } else {
            $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();
            $message   = 'Building queue failed to clear: Building Id: ' . $this->building->id . ' KingdomId: ' . $this->building->kingdom_id;

            SendOffEmail::dispatch($adminUser, (new GenericMail($adminUser, $message, 'Failed To Clear Building Queue')))->delay(now()->addMinutes(1));
        }

        if (UserOnlineValue::isOnline($this->user)) {
            $kingdom = Kingdom::find($this->building->kingdom_id);
            $plane   = $kingdom->gameMap->name;
            $kingdom = new Item($kingdom, $kingdomTransformer);
            $kingdom = $manager->createData($kingdom)->toArray();

            event(new UpdateKingdom($this->user, $kingdom));

            $x = $this->building->kingdom->x_position;
            $y = $this->building->kingdom->y_position;

            if ($this->user->show_building_upgrade_messages) {
                $message = $this->building->name . ' finished upgrading for kingdom: ' .
                    $this->building->kingdom->name . ' on plane: ' . $plane .
                    ' At (X/Y) ' . $x . '/' . $y . ' and is now level: ' . $level;

                event(new ServerMessageEvent($this->user, 'building-upgrade-finished', $message));
            }
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
