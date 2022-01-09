<?php

namespace App\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\GameBuilding;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Kingdom;
use Facades\App\Flare\Values\UserOnlineValue;

class AssignNewKingdomBuildingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var GameBuilding $gameBuilding
     */
    public $gameBuilding;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GameBuilding $gameBuilding) {
        $this->gameBuilding  = $gameBuilding;
    }

    /**
     * Job handler.
     *
     * @return void
     */
    public function handle() {
        Kingdom::chunkById(100, function ($kingdoms) {
            foreach ($kingdoms as $kingdom) {
                $building = $kingdom->buildings()->where('game_building_id', $this->gameBuilding->id)->first();

                if (is_null($building)) {
                    $kingdom->buildings()->create([
                        'game_building_id'    => $this->gameBuilding->id,
                        'kingdom_id'          => $kingdom->id,
                        'level'               => is_null($this->gameBuilding->passive) ? 1 : 0,
                        'current_defence'     => $this->gameBuilding->base_defence,
                        'current_durability'  => $this->gameBuilding->base_durability,
                        'max_defence'         => $this->gameBuilding->base_defence,
                        'max_durability'      => $this->gameBuilding->base_durability,
                        'is_locked'           => $this->gameBuilding->is_locked,
                    ]);

                    if (!is_null($kingdom->character)) {
                        $user = $kingdom->character->user;

                        $message = 'Kingdom: '.$kingdom->name.' gained a new building: ' . $this->gameBuilding->name;

                        event(new ServerMessageEvent($user, 'new-building', $message));
                    }
                }
            }
        });
    }
}
