<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\MonthlyPvpParticipant;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Request\CelestialFightRequest;
use App\Game\Battle\Request\ConjureRequest;
use App\Game\Battle\Request\MonthlyPvpFight;
use App\Game\Battle\Request\PvpFight;
use App\Game\Battle\Request\PvpFightInfo;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Battle\Services\PvpService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Http\Controllers\Controller;
use App\Game\Battle\Services\ConjureService;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
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
