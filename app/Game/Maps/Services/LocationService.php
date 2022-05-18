<?php

namespace App\Game\Maps\Services;

use App\Flare\Values\LocationEffectValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use Illuminate\Support\Collection;
use Storage;
use League\Fractal\Manager;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Location;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Models\CelestialFight;
use App\Game\Maps\Services\Common\CanPlayerMassEmbezzle;
use App\Game\Maps\Services\Common\LiveCharacterCount;
use App\Game\Core\Traits\KingdomCache;

class LocationService {

    use KingdomCache, LiveCharacterCount, CanPlayerMassEmbezzle;

    /**
     * @var PortService $portService
     */
    private $portService;

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
     * Constructor
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

        $lockedLocation = Location::where('x', $character->map->character_position_x)
                                  ->where('y', $character->map->character_position_y)
                                  ->where('game_map_id', $character->map->game_map_id)
                                  ->whereNotNull('required_quest_item_id')
                                  ->first();

        // In case automation is running, this way the timer updates.
        event(new UpdateCharacterStatus($character));

        return [
            'map_url'                => Storage::disk('maps')->url($character->map_url),
            'character_map'          => $character->map,
//            'character_id'           => $character->id,
            'locations'              => $this->fetchLocationData($character),
            'can_move'               => $character->can_move,
            'can_move_again_at'      => $character->can_move_again_at,
//            'port_details'           => $this->portDetails,
//            'map_name'               => $character->map->gameMap->name,
//            'adventure_completed_at' => $character->can_adventure_again_at,
//            'inventory_sets'         => $this->getSets($character),
//            'is_dead'                => $character->is_dead,
            'coordinates'             => $this->coordinatesCache->getFromCache(),
//            'celestials'             => $this->getCelestialEntity($character),
//            'can_settle_kingdom'     => $this->canSettle,
//            'can_attack_kingdom'     => $this->canAttack,
//            'can_manage_kingdom'     => $this->canManage,
//            'kingdom_to_attack'      => $this->kingdomToAttack,
            'my_kingdoms'            => $this->getKingdoms($character),
            'npc_kingdoms'           => Kingdom::select('id', 'x_position', 'y_position', 'npc_owned', 'name')->whereNull('character_id')->where('game_map_id', $character->map->game_map_id)->where('npc_owned', true)->get(),
            'other_kingdoms'         => $this->getEnemyKingdoms($character),
            'characters_on_map'      => $this->getActiveUsersCountForMap($character),
//            'can_mass_embezzle'      => $this->canMassEmbezzle($character, $this->canManage),
//            'lockedLocationType'     => is_null($lockedLocation) ? null : $lockedLocation->type,
        ];
    }

    protected function getSets(Character $character): array {
        $sets = [];

        foreach ($character->inventorySets as $set) {

            if ($set->slots->isEmpty()) {
                if (is_null($set->name)) {
                    $index     = $character->inventorySets->search(function($inventorySet) use ($set) {
                        return $inventorySet->id === $set->id;
                    }) + 1;

                    $sets[] = [
                        'name' => 'Set ' . $index,
                        'id'   => $set->id,
                    ];
                } else {
                    $sets[] = [
                        'name' => $set->name,
                        'id'   => $set->id,
                    ];
                }
            }
        }

        return $sets;
    }

    protected function fetchLocationData(Character $character): Collection {
        $locations = Location::with('questRewardItem')->where('game_map_id', $character->map->game_map_id)->get();

        return $locations->transform(function($location) {

            $location->increases_enemy_stats_by      = null;
            $location->increase_enemy_percentage_by  = null;

            if (!is_null($location->enemy_strength_type)) {
                $location->increases_enemy_stats_by     = LocationEffectValue::getIncreaseByAmount($location->enemy_strength_type);
                $location->increase_enemy_percentage_by = LocationEffectValue::fetchPercentageIncrease($location->enemy_strength_type);
            }

            return $location;
        });
    }

    protected function getCelestialEntity(Character $character) {
        return CelestialFight::with('monster')->join('monsters', function($join) use($character) {
            $join->on('monsters.id', 'celestial_fights.monster_id')
                ->where('x_position', $character->x_position)
                ->where('y_position', $character->y_position)
                ->where('monsters.game_map_id', $character->map->gameMap->id);
        })->select('celestial_fights.*')->get();
    }

    /**
     * Processes the location.
     *
     * We will fetch the location information for the character position.
     *
     * This includes port details and any relevant adventures the location might have.
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
        }
    }

    /**
     * Determines the action the player can take.
     *
     * Based on the character position, if there is a kingdom or not.
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
                    $this->canAttack = true;

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
