<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\MonthlyPvpParticipant;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Request\MonthlyPvpFight;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Messages\Events\ServerMessageEvent;

class MonthlyPvpParticipantsController extends Controller {


    public function join(MonthlyPvpFight $request, Character $character) {

        if ($character->level < 301) {
            event(new ServerMessageEvent($character->user, 'You need to be at least level 301 to participate.'));

            return response()->json();
        }

        $characterInFight = MonthlyPvpParticipant::where('character_id', $character->id)->first();

        if (!is_null($characterInFight)) {
            $characterInFight->update([
                'attack_type' => $request->attack_type
            ]);

            event(new ServerMessageEvent($character->user, 'Updated your pvp attack type for tonight.'));

            return response()->json();
        }

        MonthlyPvpParticipant::create(['character_id' => $character->id, 'attack_type' => $request->attack_type]);

        event(new UpdateCharacterStatus($character));

        event(new ServerMessageEvent($character->user, 'You have been registered for PVP. Make sure to be logged in at 6:15pm GMT-6 to get ready for the
        festivities! Battle starts at 6:30pm GMT-6. Should you wish to not participate you can log in after 6:30pm and you wont be included.
        Remember you have to be logged in for this!'));

        return response()->json();
    }
}
