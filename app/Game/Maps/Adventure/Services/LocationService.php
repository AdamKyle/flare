<?php

namespace App\Game\Maps\Adventure\Services;

use Cache;
use Storage;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Models\User;
use App\Flare\Transformers\KingdomTransformer;

class LocationService {

    /**
     * @var PortSevice $portService
     */
    private $portService;

    /**
     * @var array $portDetails
     */
    private $portDetails;

    /**
     * @var \Illuminate\Support\Collection $adventureDetails | null
     */
    private $adventureDetails;

    /**
     * @var Location $location | null
     */
    private $location;

    /**
     * @var KingdomTransformer $kingdomTransformer
     */
    private $kingdomTransformer;

    /**
     * @var Manager $manager
     */
    private $manager;

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
     * @var CoordinatesCache $coordinatesCache
     */
    private $coordinatesCache;

    /**
     * Contructor
     * 
     * @param PortService $portService
     * @param KingdomTransformer $kingdomTransformer
     * @param Manager $manager
     */
    public function __construct(PortService $portService, KingdomTransformer $kingdomTransfromer, Manager $manager, CoordinatesCache $coordinatesCache) {
        $this->portService        = $portService;
        $this->kingdomTransformer = $kingdomTransfromer;
        $this->manager            = $manager;
        $this->coordinatesCache   = $coordinatesCache;
    }

    /**
     * Get location data
     * 
     * @param User $user
     * @return array
     */
    public function getLocationData(User $user): array {
        $this->processLocation($user);

        $this->kingdomManagement($user);  

        return [
            'map_url'                => Storage::disk('maps')->url($user->character->map->gameMap->path),
            'character_map'          => $user->character->map,
            'character_id'           => $user->character->id,
            'locations'              => Location::with('adventures', 'questRewardItem')->get(),
            'can_move'               => $user->character->can_move,
            'timeout'                => $user->character->can_move_again_at,
            'show_message'           => $user->character->can_move ? false : true,
            'port_details'           => $this->portDetails,
            'adventure_details'      => $this->adventureDetails,
            'adventure_logs'         => $user->character->adventureLogs,
            'adventure_completed_at' => $user->character->can_adventure_again_at,
            'is_dead'                => $user->character->is_dead,
            'teleport'               => $this->coordinatesCache->getFromCache(),
            'can_settle_kingdom'     => $this->canSettle,
            'can_attack_kingdom'     => $this->canAttack,
            'can_manage_kingdom'     => $this->canManage,
            'my_kingdoms'            => $this->getKingdomsFromCache($user->character),
        ];
    }

    protected function getKingdomsFromCache(Character $character) {
        if (Cache::has('character-kingdoms-' . $character->id)) {
            return Cache::get('character-kingdoms-' . $character->id);
        }

        $kingdoms = Kingdom::select('id', 'x_position', 'y_position', 'color')->where('character_id', $character->id)->get();
            
        Cache::put('character-kingdoms-' . $character->id, $kingdoms);

        return Cache::get('character-kingdoms-' . $character->id);
    }

    protected function processLocation(User $user) {
        $this->location = Location::where('x', $user->character->map->character_position_x)
                                  ->where('y', $user->character->map->character_position_y)
                                  ->first();

        if (!is_null($this->location)) {
            if ($this->location->is_port) {
                $this->portDetails = $this->portService->getPortDetails($user->character, $this->location);
            }
            
            $this->adventureDetails = $this->location->adventures;
        }
    }

    protected function kingdomManagement(User $user) {
        $kingdom   = Kingdom::where('x_position', $user->character->map->character_position_x)
                            ->where('y_position', $user->character->map->character_position_y)
                            ->first();
        

        if (!is_null($kingdom)) {
            if (auth()->user()->id !== $kingdom->character->user->id) {
                $this->canAttack = true;
            } else {
                $this->canManage = true;
            }
        } else if (is_null($this->location)) {
            $this->canSettle = true;
        }
    }
}