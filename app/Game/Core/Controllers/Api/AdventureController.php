<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Adventure;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Core\Events\EmbarkOnAdventureEvent;
use App\Game\Core\Events\UpdateAdventureLogsBroadcastEvent;
use Cache;
use Illuminate\Http\Request;

class AdventureController extends Controller {


    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead');
        $this->middleware('is.character.adventuring')->except(['cancelAdventure']);
    }

    public function adventure(Request $request, Character $character, Adventure $adventure) {
        $character->update([
            'can_attack'    => false,
            'can_move'      => false,
            'can_craft'     => false,
            'can_adventure' => false,
        ]);

        $foundAdventureLog = $character->adventureLogs->where('adventure_id', $adventure->id)->first();

        if (!is_null($foundAdventureLog)) {
            $lastCompletedLevel = $foundAdventureLog->last_completed_level;

            if (!is_null($lastCompletedLevel)) {
                if ($lastCompletedLevel === $adventure->levels) {
                    $lastCompletedLevel = null;
                }
            }

            $foundAdventureLog->update([
                'in_progress'          => true,
                'complete'             => false,
                'last_completed_level' => $lastCompletedLevel,
            ]);
        } else {
            $character->adventureLogs()->create([
                'character_id' => $character->id,
                'adventure_id' => $adventure->id,
                'in_progress'  => true,
            ]);
        }

        $character = $character->refresh();

        event(new EmbarkOnAdventureEvent($character, $adventure, $request->levels_at_a_time));

        event(new UpdateAdventureLogsBroadcastEvent($character->adventureLogs, $character->user));

        return response()->json([
            'message'                => 'Adventure has started!',
            'adventure_completed_at' => $character->can_adventure_again_at,
        ], 200);
    }

    public function cancelAdventure(Character $character, Adventure $adventure) {
        $character->update([
            'can_attack'             => true,
            'can_move'               => true,
            'can_craft'              => true,
            'can_adventure'          => true,
            'can_adventure_again_at' => null,
        ]);

        $adventureLog = $character->adventureLogs->where('adventure_id', $adventure->id)->first();

        $adventureLog->update([
            'in_progress' => false,
        ]);

        Cache::forget('character_'.$character->id.'_adventure_'.$adventure->id);

        event(new UpdateAdventureLogsBroadcastEvent($character->adventureLogs, $character->user));

        return response()->json([
            'message'        => 'Adventure canceled.',
        ], 200);
    }
}
