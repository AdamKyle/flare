<?php

namespace App\Game\Maps\Adventure\Controllers\Api;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use Storage;
use Illuminate\Http\Request;
use App\Flare\Models\User;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Requests\SetSailValidation;
use App\Game\Maps\Adventure\Services\PortService;
use App\Game\Maps\Adventure\Values\MapTileValue;

class MapController extends Controller {

    private $portService;

    private $water;

    public function __construct(PortService $portService, MapTileValue $mapTile) {

        $this->portService = $portService;
        $this->mapTile     = $mapTile;

        $this->middleware('auth:api');
        $this->middleware('is.character.adventuring')->except(['index']);
        $this->middleware('is.character.dead')->except(['index']);
    }

    public function index(Request $request, User $user) {
        $location         = Location::where('x', $user->character->map->character_position_x)->where('y', $user->character->map->character_position_y)->first();
        $portDetails      = null;
        $adventureDetails = null;

        if (!is_null($location)) {
            if ($location->is_port) {
                $portDetails      = $this->portService->getPortDetails($user->character, $location);
            }
            
            $adventureDetails = $location->adventures;
        }

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
        ]);
    }

    public function move(Request $request, Character $character) {

        $character->map->update([
            'character_position_x' => $request->character_position_x,
            'character_position_y' => $request->character_position_y,
            'position_x'           => $request->position_x,
            'position_y'           => $request->position_y,
        ]);

        $location        = Location::where('x', $request->character_position_x)->where('y', $request->character_position_y)->first();
        
        $portDetails = [];
        $adventureDetails = [];

        if (!is_null($location)) {
            if ($location->is_port) {
                $portDetails = $this->portService->getPortDetails($character, $location);
            }
    
            if (!is_null($location->questRewardItem)) {
                $item = $character->inventory->slots->filter(function($slot) use ($location) {
                    return $slot->item_id === $location->questRewardItem->id;
                })->first();
    
                if (is_null($item)) {
                    $character->inventory->slots()->create([
                        'inventory_id' => $character->inventory->id,
                        'item_id'      => $location->questRewardItem->id,
                    ]);
    
                    event(new ServerMessageEvent($character->user, 'found_item', $location->questRewardItem->name));
                }
            }

            if ($location->adventures->isNotEmpty()) {
                $adventureDetails = $location->adventures;
            }
        }
        
        $character->update([
            'can_move'          => false,
            'can_move_again_at' => now()->addSeconds(10),
        ]);

        event(new MoveTimeOutEvent($character));

        return response()->json([
            'port_details' => $portDetails,
            'adventure_details' => $adventureDetails,
        ], 200);
    }

    public function setSail(SetSailValidation $request, Location $location, Character $character) {
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

        if (!is_null($location->questRewardItem)) {
            $item = $character->inventory->slots->filter(function($slot) use ($location) {
                return $slot->item_id === $location->questRewardItem->id;
            })->first();

            if (is_null($item)) {
                $character->inventory->slots()->create([
                    'inventory_id' => $character->inventory->id,
                    'item_id'      => $location->questRewardItem->id,
                ]);

                event(new ServerMessageEvent($character->user, 'found_item', $location->questRewardItem->name));
            }
        }
        
        event(new MoveTimeOutEvent($character, $request->time_out_value, true));
        event(new UpdateTopBarEvent($character));

        return response()->json([
            'character_position_details' => $character->map,
            'port_details'               => $this->portService->getPortDetails($character, $location),
            'adventure_details'          => $location->adventures->isNotEmpty() ? $location->adventures : [],
        ]);
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
