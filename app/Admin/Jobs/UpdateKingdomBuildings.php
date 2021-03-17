<?php

namespace App\Admin\Jobs;

use App\Admin\Mail\GenericMail;
use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\GameKingdomBuilding;
use App\Admin\Services\UpdateKingdomsService;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Kingdom;
use Facades\App\Flare\Values\UserOnlineValue;

class UpdateKingdomBuildings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $gameKingdomBuilding;

    public $selectedUnits;

    public $levels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GameKingdomBuilding $gameKingdomBuilding, array $selectedUnits = [], int $levels = null) {
        $this->gameKingdomBuilding  = $gameKingdomBuilding;
        $this->selectedUnits = $selectedUnits;
        $this->levels        = $levels;
    }

    /**
     * 
     * @return void
     */
    public function handle(UpdateKingdomsService $service) {
        $query = KingdomBuilding::where('game_building_id', $this->gameKingdomBuilding->id);

        $this->reassignUnits($service);

        if ($query->get()->isEmpty()) {
            // If no kingdom has this building:
            Kingdom::chunkById(500, function($kingdoms) {
                foreach($kingdoms as $kingdom) {
                    $kingdom->buildings()->create([
                        'game_building_id'    => $this->gameKingdomBuilding->id,
                        'kingdom_id'         => $kingdom->id,
                        'level'               => 1,
                        'current_defence'     => $this->gameKingdomBuilding->base_defence,
                        'current_durability'  => $this->gameKingdomBuilding->base_durability,
                        'max_defence'         => $this->gameKingdomBuilding->base_defence,
                        'max_durability'      => $this->gameKingdomBuilding->base_durability,
                    ]);

                    $user      = $kingdom->character->user;
                    $character = $kingdom->character;

                    $message = 'Kingdom: '.$kingdom->name.' gained a new building: ' . $this->gameKingdomBuilding->name;

                    if (UserOnlineValue::isOnline($user)) {
                        
                        event(new ServerMessageEvent($user, 'new-building', $message));
                    } else if ($user->new_building_email) {
                        Mail::to($user->email)->send(new GenericMail($character->user, $message, 'New KingdomBuilding!'));
                    }
                }
            });
        } else {
            // If kingdoms do not have this building:
            $query->chunkById(1000, function($buildings) {
                foreach($buildings as $building) {
                    UpdateKingdomBuilding::dispatch($building)->delay(now()->addMinutes(1));
                }
            });
        }
    }

    public function reassignUnits(UpdateKingdomsService $service) {
        if (empty($this->selectedUnits)) {
            return;
        }
        
        if ($this->gameKingdomBuilding->units->isNotEmpty()) {
            foreach($this->gameKingdomBuilding->units as $unit) {
                $unit->delete();
            }
        }
        
        $service->assignUnits($this->gameKingdomBuilding->refresh(), $this->selectedUnits, $this->levels);

        $this->gameKingdomBuilding = $this->gameKingdomBuilding->refresh();
    }
}
