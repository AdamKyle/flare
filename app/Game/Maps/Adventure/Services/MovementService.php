<?php

namespace App\Game\Maps\Adventure\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;

class MovementService {

    /**
     * @var array $portDetails
     */
    private $portDetails = [];

    /**
     * @var array $adventureDetails
     */
    private $adventureDetails = [];

    /**
     * @var array $kingdomData
     */
    private $kingdomData = [];

    /**
     * @var PortService $portService
     */
    private $portService = null;

    public function __construct(PortService $portService) {
        $this->portService = $portService;
    }

    public function processArea(int $x, int $y, Character $character) {
        $location = Location::where('x', $x)->where('y', $y)->first();

        if (!is_null($location)) {
            $this->processLocation($location, $character);
        }

        $kingdom = Kingdom::where('x_position', $x)->where('y_position', $y)->first();

        $canAttack = false;
        $canSettle = false;
        $canManage = false;

        if (!is_null($kingdom)) {
            if ($kingdom->character->user->id !== auth()->user()->id) {
                $canAttack = true;
            } else {
                $canManage = true;
            }
        } else if (is_null($location)) {
            $canSettle = true;
        }

        $this->kingdomData = [
            'owner'      => is_null($kingdom) ? 'No one' : $kingdom->character->name,
            'can_attack' => $canAttack,
            'can_manage' => $canManage,
            'can_settle' => $canSettle,
        ];
    }


    /**
     * Process the location for ports and adventures as well as drops.
     * 
     * @param Location $location | null
     * @param Character $character
     * @param PortService $portService
     * @return void
     */
    public function processLocation(Location $location, Character $character): void {
        if ($location->is_port) {
            $this->portDetails = $this->portService->getPortDetails($character, $location);
        }

        $this->giveQuestReward($location, $character);

        if ($location->adventures->isNotEmpty()) {
            $this->adventureDetails = $location->adventures->toArray();
        }
    }

    /**
     * Get the port details
     * 
     * @return array
     */
    public function portDetails(): array {
        return $this->portDetails;
    }

    /**
     * Get the adventure details
     * 
     * @return array
     */
    public function adventureDetails(): array {
        return $this->adventureDetails;
    }

    /**
     * Get the kingdom data
     * 
     * @param array
     */
    public function kingdomDetails(): array {
        return $this->kingdomData;
    }

    /**
     * Give the quest reward item
     * 
     * @param Location $location
     * @param Character $character
     * @param void
     */
    public function giveQuestReward(Location $location, Character $character): void {
        if (!is_null($location->questRewardItem)) {
            $item = $character->inventory->slots->filter(function($slot) use ($location) {
                return $slot->item_id === $location->questRewardItem->id;
            })->first();

            if (is_null($item)) {
                
                if (!($character->inventory->slots()->count() < $character->inventory_max)) {
                    event(new ServerMessageEvent($character->user, 'inventory_full'));
                } else {
                    $character->inventory->slots()->create([
                        'inventory_id' => $character->inventory->id,
                        'item_id'      => $location->questRewardItem->id,
                    ]);

                    event(new ServerMessageEvent($character->user, 'found_item', $location->questRewarditem->affix_name));
                }
            }
        }
    }
}