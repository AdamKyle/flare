<?php

namespace App\Game\Maps\Services;

use Storage;
use League\Fractal\Manager;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Core\Traits\KingdomCache;

class LocationService {

    use KingdomCache;

    /**
     * @var PortSevice $portService
     */
    private $portService;

    /**
     * @var \Illuminate\Support\Collection $adventureDetails | null
     */
    private $adventureDetails;

    /**
     * @var CoordinatesCache $coordinatesCache
     */
    private $coordinatesCache;

    /**
     * @var array $portDetails
     */
    private $portDetails;

    /**
     * @var Location $location | null
     */
    private $location;

    /**
     * @var bool $canSettle | false
     */
    private $canSettle = false;

    /**
     * @var bool $canAttack | false
     */
    private $canAttack = false;
    
    /**
     * @var bool $canSettle | false
     */
    private $canManage = false;

    /**
     * Contructor
     * 
     * @param PortService $portService
     * @param KingdomTransformer $kingdomTransformer
     * @param Manager $manager
     */
    public function __construct(PortService $portService, CoordinatesCache $coordinatesCache) {
        $this->portService        = $portService;
        $this->coordinatesCache   = $coordinatesCache;
    }

    /**
     * Get location data
     * 
     * @param Character $character
     * @return array
     */
    public function getLocationData(Character $character): array {
        $this->processLocation($character);

        $this->kingdomManagement($character);  

        return [
            'map_url'                => Storage::disk('maps')->url($character->map_url),
            'character_map'          => $character->map,
            'character_id'           => $character->id,
            'locations'              => Location::with('adventures', 'questRewardItem')->get(),
            'can_move'               => $character->can_move,
            'timeout'                => $character->can_move_again_at,
            'port_details'           => $this->portDetails,
            'adventure_details'      => $this->adventureDetails,
            'adventure_logs'         => $character->adventureLogs,
            'adventure_completed_at' => $character->can_adventure_again_at,
            'is_dead'                => $character->is_dead,
            'teleport'               => $this->coordinatesCache->getFromCache(),
            'can_settle_kingdom'     => $this->canSettle,
            'can_attack_kingdom'     => $this->canAttack,
            'can_manage_kingdom'     => $this->canManage,
            'my_kingdoms'            => $this->getKingdoms($character),
        ];
    }

    /**
     * Processes the location.
     * 
     * We will fetch the location information for the character postion.
     * 
     * This includes port details and any relavant adventures the location might have.
     * 
     * @param Character $character
     * @return void
     */
    protected function processLocation(Character $character): void {
        $this->location = Location::where('x', $character->x_position)
                                  ->where('y', $character->y_position)
                                  ->first();

        if (!is_null($this->location)) {
            if ($this->location->is_port) {
                $this->portDetails = $this->portService->getPortDetails($character, $this->location);
            }
            
            $this->adventureDetails = $this->location->adventures;
        }
    }

    /**
     * Determines the action the player can take.
     * 
     * Based on he character position, if there is a kingdom or not
     * We determine the action the player can take. That is, can they settle?
     * Can they attack the kingdom or can they manage the kingdom?
     * 
     * @param Character $character
     * @return void
     */
    protected function kingdomManagement(Character $character): void {
        $kingdom   = Kingdom::where('x_position', $character->x_position)
                            ->where('y_position', $character->y_position)
                            ->first();
        

        if (!is_null($kingdom)) {
            if ($character->id !== $kingdom->character->id) {
                $this->canAttack = true;
            } else {
                $this->canManage = true;
            }
        } else if (is_null($this->location)) {
            $this->canSettle = true;
        }
    }
}