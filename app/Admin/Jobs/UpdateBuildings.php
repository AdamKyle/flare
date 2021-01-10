<?php

namespace App\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Building;
use App\Flare\Models\GameBuilding;

class UpdateBuildings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $gameBuilding;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GameBuilding $gameBuilding) {
        $this->gameBuilding = $gameBuilding;
    }

    /**
     * 
     * @return void
     */
    public function handle() {
        Building::where('game_building_id', $this->gameBuilding->id)->chunkById(1000, function($buildings) {
            foreach($buildings as $building) {
                UpdateBuilding::dispatch($building)->delay(now()->addMinutes(1));
            }
        });
    }
}
