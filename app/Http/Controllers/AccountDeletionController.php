<?php

namespace App\Http\Controllers;

use App\Flare\Events\CreateCharacterEvent;
use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Models\Adventure;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;
use App\Flare\Services\CharacterDeletion;
use Cache;
use Illuminate\Http\Request;
use Storage;

class AccountDeletionController extends Controller {

    public function deleteAccount(User $user) {
        if (auth()->user()->id !== $user->id) {
            return redirect()->back()->with('error', 'You cannot do that.');
        }

        \Auth::logout();

        AccountDeletionJob::dispatch($user)->delay(now()->addMinutes(1))->onConnection('long_running');

        return redirect()->to('/')->with('success', 'Account deletion underway. You will receive one last email when it\'s done.');
    }

    public function resetAccount(Request $request, User $user, CharacterDeletion $characterDeletion) {
         $character     = $user->character;
         $currentRace   = $character->game_race_id;
         $currentClass  = $character->game_class_id;
         $characterName = $character->name;

         if (is_null($request->class)) {
             $request->merge([
                 'class' => $currentClass
             ]);
         }

        if (is_null($request->race)) {
            $request->merge([
                'race' => $currentRace
            ]);
        }

        if ($request->has('guide_enabled')) {
            $user->update([
                'guide_enabled' => true,
            ]);
        }  else {
            $user->update([
                'guide_enabled' => false,
            ]);
        }

        $characterDeletion->deleteCharacterFromUser($character);

        $user = $user->refresh();

        event(new CreateCharacterEvent($user, GameMap::first(), $request, $characterName));

        return response()->redirectToRoute('game')->with('success', 'Character has been re-rolled!');
    }
}
