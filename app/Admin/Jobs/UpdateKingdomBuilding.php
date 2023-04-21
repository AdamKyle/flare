<?php

namespace App\Admin\Jobs;

use App\Flare\Models\GameBuilding;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;

class UpdateKingdomBuilding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var KingdomBuilding $building
     */
    public $building;

    /**
     * @var KingdomBuilding $gameBuilding
     */
    public $gameBuilding;

    /**
     * Create a new job instance.
     *
     * @param KingdomBuilding $building
     * @param GameBuilding $gameBuilding
     * @return void
     */
    public function __construct(KingdomBuilding $building, GameBuilding $gameBuilding) {
        $this->building     = $building;
        $this->gameBuilding = $gameBuilding;
    }

    /**
     * Handle method.
     *
     * @param Manager $manager
     * @param KingdomTransformer $kingdomTransformer
     * @return void
     */
    public function handle(Manager $manager, KingdomTransformer $kingdomTransformer) {
        $this->building->update([
            'current_durability' => $this->building->durability,
            'current_defence'    => $this->building->defence,
            'max_defence'        => $this->building->defence,
            'max_durability'     => $this->building->durability,
        ]);

        if (!is_null($this->building->kingdom->character)) {
            $building = $this->building->refresh();
            $kingdom  = new Item($building->kingdom, $kingdomTransformer);
            $kingdom  = $manager->createData($kingdom)->toArray();
            $user     = $building->kingdom->character->user;

            event(new UpdateKingdom($user, $kingdom));
        }
    }
}
