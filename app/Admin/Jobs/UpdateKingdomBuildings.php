<?php

namespace App\Admin\Jobs;

use App\Flare\Mail\GenericMail;
use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\GameBuilding;
use App\Admin\Services\UpdateKingdomsService;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Kingdom;
use Facades\App\Flare\Values\UserOnlineValue;

class UpdateKingdomBuildings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var GameBuilding $gameBuilding
     */
    public $gameBuilding;

    /**
     * @var array $selectedUnits
     */
    public $selectedUnits;

    /**
     * @var int|null $levels
     */
    public $levels;

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
    public function handle() {
        $query = KingdomBuilding::where('game_building_id', $this->gameBuilding->id);

        $this->reassignUnits();

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
                            event(new ServerMessageEvent($user, 'new-building', $message));
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

    /**
     * Reassigns the units to a building.
     *
     * @return void
     */
    protected function reassignUnits(): void {
        if (empty($this->selectedUnits)) {
            return;
        }

        if ($this->gameBuilding->units->isNotEmpty()) {
            foreach($this->gameBuilding->units as $unit) {
                $unit->delete();
            }
        }

        $this->assignUnits($this->gameBuilding->refresh(), $this->selectedUnits, $this->levels);

        $this->gameBuilding = $this->gameBuilding->refresh();
    }

    /**
     * Assigns the units to the building.
     *
     * @param GameBuilding $gameBuilding
     * @param array $selectedUnits
     * @param int $levels
     * @return void
     */
    private function assignUnits(GameBuilding $gameBuilding, array $selectedUnits, int $levels): void {
        $gameBuilding->units()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id'     => $selectedUnits[0],
            'required_level'   => !is_null($gameBuilding->only_at_level) ? $gameBuilding->only_at_level : 1,
        ]);

        unset($selectedUnits[0]);

        $initialLevel = 1;

        if (empty($selectedUnits)) {
            return;
        }

        foreach($selectedUnits as $unitId) {
            $initialLevel += $levels;

            $gameBuilding->units()->create([
                'game_building_id' => $gameBuilding->id,
                'game_unit_id'     => $unitId,
                'required_level'   => $initialLevel,
            ]);
        }
    }
}
