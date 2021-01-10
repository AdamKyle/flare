<?php

namespace App\Admin\Jobs;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Building;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;

class UpdateBuilding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $building;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Building $building) {
        $this->building     = $building;
    }

    /**
     * 
     * @return void
     */
    public function handle(Manager $manager, KingdomTransformer $kingdomTransformer) {
        $this->building->update([
            'current_defence'    => $this->building->defence,
            'current_durability' => $this->building->durability,
            'max_defence'        => $this->building->defence,
            'max_durability'     => $this->building->durability,
        ]);

        $building = $this->building->refresh();

        $kingdom = new Item($building->kingdom, $kingdomTransformer);
        $kingdom = $manager->createData($kingdom)->toArray();
        $user    = $building->kingdom->character->user;

        event(new UpdateKingdom($user, $kingdom));
    }
}
