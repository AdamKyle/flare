<?php

namespace App\Game\Maps\Services;

use App\Flare\Models\CelestialFight;
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
     * Stores the id and the location information
     *
     * @var array $kingdomToAttack
     */
    private $kingdomToAttack = [];

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
            'locations'              => Location::with('adventures', 'questRewardItem')->where('game_map_id', $character->map->game_map_id)->get(),
            'can_move'               => $character->can_move,
            'timeout'                => $character->can_move_again_at,
            'port_details'           => $this->portDetails,
            'map_name'               => $character->map->gameMap->name,
            'adventure_details'      => $this->adventureDetails,
            'adventure_logs'         => $character->adventureLogs,
            'adventure_completed_at' => $character->can_adventure_again_at,
            'is_dead'                => $character->is_dead,
            'teleport'               => $this->coordinatesCache->getFromCache(),
            'celestials'             => CelestialFight::where('x_position', $character->x_position)->where('y_position', $character->y_position)->with('monster')->get()->toArray(),
            'can_settle_kingdom'     => $this->canSettle,
            'can_attack_kingdom'     => $this->canAttack,
            'can_manage_kingdom'     => $this->canManage,
            'kingdom_to_attack'      => $this->kingdomToAttack,
            'my_kingdoms'            => $this->getKingdoms($character),
            'npc_kingdoms'           => Kingdom::select('x_position', 'y_position', 'npc_owned')->whereNull('character_id')->where('game_map_id', $character->map->game_map_id)->where('npc_owned', true)->get(),
            'other_kingdoms'         => $this->getEnemyKingdoms($character),
            'characters_on_map'      => Character::join('maps', function($query) use ($character) {
                $mapId = $character->map->game_map_id;
                $query->on('characters.id', 'maps.character_id')->where('game_map_id', $mapId);
            })->count(),
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
                                  ->where('game_map_id', $character->map->game_map_id)
                                  ->first();

        if (!is_null($this->location)) {
            if ($this->location->is_port) {
                $this->portDetails = $this->portService->getPortDetails($character, $this->location);
            }

            $this->adventureDetails = $this->location->adventures()->where('published', true)->get();
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
                            ->where('game_map_id', $character->map->game_map_id)
                            ->first();

        // See if the characters kingdoms
        $units = $character->kingdoms()->where('game_map_id', $character->map->game_map_id)->join('kingdom_units', function($join) {
            $join->on('kingdoms.id', 'kingdom_units.kingdom_id')
                 ->where('kingdom_units.amount', '>', 0);
        })->get();

        if (!is_null($kingdom)) {
            if (!is_null($kingdom->character_id)) {
                if ($character->id !== $kingdom->character->id) {
                    $this->canAttack = $units->isNotEmpty();

                    $this->kingdomToAttack = [
                        'id' => $kingdom->id,
                        'x_position' => $kingdom->x_position,
                        'y_position' => $kingdom->y_position,
                    ];
                } else {
                    $this->canManage = true;

                    $kingdom->updateLastWalked();
                }
            } else {
                // You can attack npc kingdoms.
                $this->canAttack = $units->isNotEmpty();

                $this->kingdomToAttack = [
                    'id' => $kingdom->id,
                    'x_position' => $kingdom->x_position,
                    'y_position' => $kingdom->y_position,
                ];
            }
        } else if (is_null($this->location)) {
            $this->canSettle = true;
        }
    }
}
