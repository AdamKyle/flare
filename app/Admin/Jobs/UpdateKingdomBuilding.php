<?php

namespace App\Admin\Jobs;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UpdateKingdomBuilding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var KingdomBuilding
     */
    public $building;

    /**
     * @var KingdomBuilding
     */
    public $gameBuilding;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(KingdomBuilding $building, GameBuilding $gameBuilding)
    {
        $this->building = $building;
        $this->gameBuilding = $gameBuilding;
    }

    /**
     * Handle method.
     *
     * @return void
     */
    public function handle(Manager $manager, KingdomTransformer $kingdomTransformer)
    {
        $this->building->update([
            'current_durability' => $this->building->durability,
            'current_defence' => $this->building->defence,
            'max_defence' => $this->building->defence,
            'max_durability' => $this->building->durability,
        ]);

        if (! is_null($this->building->kingdom->character)) {
            $building = $this->building->refresh();
            $kingdom = new Item($building->kingdom, $kingdomTransformer);
            $kingdom = $manager->createData($kingdom)->toArray();
            $user = $building->kingdom->character->user;

            event(new UpdateKingdom($user, $kingdom));
        }
    }
}
