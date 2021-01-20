<?php

namespace App\Game\Maps\Adventure\Controllers\Api;

use Storage;
use Illuminate\Http\Request;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Adventure\Requests\SetSailValidation;
use App\Game\Maps\Adventure\Services\MovementService;
use App\Game\Maps\Adventure\Services\PortService;
use App\Game\Maps\Adventure\Values\MapTileValue;
use App\Game\Maps\Values\MapPositionValue;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class MapController extends Controller {

    private $portService;

    private $coordinatesCache;

    private $mapTile;

    private $mapPositionValue;

    public function __construct(PortService $portService, MapTileValue $mapTile, CoordinatesCache $coordinatesCache, MapPositionValue $mapPositionValue) {

        $this->portService      = $portService;
        $this->mapTile          = $mapTile;
        $this->coordinatesCache = $coordinatesCache;
        $this->mapPositionValue = $mapPositionValue;

        $this->middleware('auth:api');
        $this->middleware('is.character.adventuring')->except(['index']);
        $this->middleware('is.character.dead')->except(['index']);
    }

    public function index(User $user, Manager $manager, KingdomTransformer $kingdomTransformer) {
        $location         = Location::where('x', $user->character->map->character_position_x)->where('y', $user->character->map->character_position_y)->first();
        $portDetails      = null;
        $adventureDetails = null;

        if (!is_null($location)) {
            if ($location->is_port) {
                $portDetails      = $this->portService->getPortDetails($user->character, $location);
            }
            
            $adventureDetails = $location->adventures;
        }

        $kingdom   = Kingdom::where('x_position', $user->character->map->character_position_x)->where('y_position', $user->character->map->character_position_y)->first();
        $canSettle = false;
        $canAttack = false;
        $canManage = false;

        if (!is_null($kingdom)) {
            if (auth()->user()->id !== $kingdom->character->user->id) {
                $canAttack = true;
            } else {
                $canManage = true;
            }
        } else if (is_null($location)) {
            $canSettle = true;
        }

        $myKingdoms = Kingdom::where('character_id', $user->character->id)->get();
        $kingdoms   = new Collection($myKingdoms, $kingdomTransformer);
        $kingdoms   = $manager->createData($kingdoms)->toArray();  

        return response()->json([
            'map_url'                => Storage::disk('maps')->url($user->character->map->gameMap->path),
            'character_map'          => $user->character->map,
            'character_id'           => $user->character->id,
            'locations'              => Location::with('adventures', 'questRewardItem')->get(),
            'can_move'               => $user->character->can_move,
            'timeout'                => $user->character->can_move_again_at,
            'show_message'           => $user->character->can_move ? false : true,
            'port_details'           => $portDetails,
            'adventure_details'      => $adventureDetails,
            'adventure_logs'         => $user->character->adventureLogs,
            'adventure_completed_at' => $user->character->can_adventure_again_at,
            'is_dead'                => $user->character->is_dead,
            'teleport'               => $this->coordinatesCache->getFromCache(),
            'can_settle_kingdom'     => $canSettle,
            'can_attack_kingdom'     => $canAttack,
            'can_manage_kingdom'     => $canManage,
            'my_kingdoms'            => $kingdoms,
        ]);
    }

    public function move(Request $request, Character $character, MovementService $service) {

        $character->map->update([
            'character_position_x' => $request->character_position_x,
            'character_position_y' => $request->character_position_y,
            'position_x'           => $request->position_x,
            'position_y'           => $request->position_y,
        ]);

        $service->processArea($request->character_position_x, $request->character_position_y, $character);
        
        $character->update([
            'can_move'          => false,
            'can_move_again_at' => now()->addSeconds(10),
        ]);

        event(new MoveTimeOutEvent($character));

        return response()->json([
            'port_details'      => $service->portDetails(),
            'adventure_details' => $service->adventureDetails(),
            'kingdom_details'   => $service->kingdomDetails(),
        ], 200);
    }

    public function setSail(SetSailValidation $request, Location $location, Character $character, MovementService $service ) {
        $fromPort = Location::where('id', $request->current_port_id)->where('is_port', true)->first();

        if (is_null($fromPort)) {
            return response()->json([
                'message' => 'This is not a recognized port.',
            ], 422);
        }

        if ($character->gold < $request->cost) {
            return response()->json([
                'message' => 'Not enough gold.',
            ], 422);
        }

        if (!$this->portService->doesMatch($character, $fromPort, $location, (int) $request->time_out_value, (int) $request->cost)) {
            return response()->json([
                'message' => 'Invalid input. Please refresh and try again.',
            ], 422);
        }

        $character->update([
            'can_move'          => false,
            'gold'              => $character->gold - $request->cost,
            'can_move_again_at' => now()->addMinutes($request->time_out_value),
        ]);

        $this->portService->setSail($character, $location);

        $service->giveQuestReward($location, $character);
        
        event(new MoveTimeOutEvent($character, $request->time_out_value, true));
        event(new UpdateTopBarEvent($character));

        return response()->json([
            'character_position_details' => $character->map,
            'port_details'               => $this->portService->getPortDetails($character, $location),
            'adventure_details'          => $location->adventures->isNotEmpty() ? $location->adventures : [],
        ]);
    }

    public function teleport(Request $request, Character $character, MovementService $service) {
        $color = $this->mapTile->getTileColor($character, $request->x, $request->y);

        if ($this->mapTile->isWaterTile((int) $color)) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === 'walk-on-water';
            })->isNotEmpty();

            if (!$hasItem) {
                return response()->json([
                    'message' => 'Cannot teleport to water locations without a Flask of Fresh Air.'
                ], 422);
            }
        }

        if ($character->gold < $request->cost) {
            return response()->json([
                'message' => 'Not enough gold.'
            ], 422);
        }

        $coordinates = $this->coordinatesCache->getFromCache();

        if (!in_array($request->x, $coordinates['x']) && !in_array($request->x, $coordinates['y'])) {
            return response()->json([
                'message' => 'Invalid input.'
            ], 422);
        }

        $service->processArea($request->x, $request->y, $character);

        $character->update([
            'can_move'          => false,
            'gold'              => $character->gold - $request->cost,
            'can_move_again_at' => now()->addMinutes($request->time),
        ]);
        
        $character->map()->update([
            'character_position_x' => $request->x,
            'character_position_y' => $request->y,
            'position_x'           => $this->mapPositionValue->fetchXPosition($character->map->character_position_x, $character->map->position_x),
            'position_y'           => $this->mapPositionValue->fetchYPosition($character->map->character_position_y),
        ]);

        $character = $character->refresh();
        
        event(new MoveTimeOutEvent($character, $request->timeout, true));
        event(new UpdateTopBarEvent($character));

        event(new UpdateMapDetailsBroadcast($character->map, $character->user, $service));

        return response()->json([], 200);
    }

    public function isWater(Request $request, Character $character) {
        
        $color = $this->mapTile->getTileColor($character, $request->character_position_x, $request->character_position_y);

        if ($this->mapTile->isWaterTile((int) $color)) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === 'walk-on-water';
            })->isNotEmpty();

            if (!$hasItem) {
                return response()->json([], 422);
            }
        }

        return response()->json([], 200);
    }
}
