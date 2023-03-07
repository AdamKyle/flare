<?php

namespace App\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use Facades\App\Flare\Values\UserOnlineValue;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class UpdateKingdomBuildings implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var GameBuilding $gameBuilding
     */
    public GameBuilding $gameBuilding;

    /**
     * @var array $selectedUnits
     */
    public array $selectedUnits;

    /**
     * @var int|null $levels
     */
    public ?int $levels = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GameBuilding $gameBuilding, array $selectedUnits = [], int $levels = null) {
        $this->gameBuilding  = $gameBuilding;
        $this->selectedUnits = $selectedUnits;
        $this->levels        = $levels;
    }

    /**
     * Job handler.
     *
     * @return void
     */
    public function handle(): void {
        $query = KingdomBuilding::where('game_building_id', $this->gameBuilding->id);

        if ($query->get()->isEmpty()) {
            // If no kingdom has this building:
            Kingdom::chunkById(500, function($kingdoms) {
                foreach($kingdoms as $kingdom) {

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
                        $user      = $kingdom->character->user;

                        $message = 'Kingdom: '.$kingdom->name.' gained a new building: ' . $this->gameBuilding->name;

                        if (UserOnlineValue::isOnline($user)) {
                            ServerMessageHandler::handleMessage($user, 'new_building', $message);
                        }
                    }
                }
            });
        } else {
            $query->chunkById(1000, function($buildings) {
                foreach($buildings as $building) {
                    UpdateKingdomBuilding::dispatch($building, $this->gameBuilding)->delay(now()->addMinutes(1));
                }
            });
        }
    }
}
