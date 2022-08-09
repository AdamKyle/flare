<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Models\Adventure;
use App\Flare\Models\User;
use App\Flare\Services\CharacterDeletion;


class AccountDeletionController extends Controller {

    public function deleteAccount(User $user) {
        if (auth()->user()->id !== $user->id) {
            return redirect()->back()->with('error', 'You cannot do that.');
        }

        \Auth::logout();

        AccountDeletionJob::dispatch($user, true)->delay(now()->addMinutes(1))->onConnection('long_running');

        return redirect()->to('/')->with('success', 'Account deletion underway. You will receive one last email when it\'s done.');
    }

    public function resetAccount(Request $request, User $user, CharacterDeletion $characterDeletion) {
         $character     = $user->character;

         $data = [
             'race_id'  => $character->race->id,
             'class_id' => $character->class->id,
             'name'     => $character->name,
             'guide'    => false,
         ];

         if ($request->has('class')) {
             $data['class_id'] = !is_null($request->class) ? $request->class : $character->race->id;
         }

        if ($request->has('race')) {
            $data['race_id'] = !is_null($request->race) ? $request->race : $character->race->id;
        }

        $data['guide'] = $request->has('guide_enabled');

        $characterDeletion->deleteCharacterFromUser($character, $data);

        return response()->redirectToRoute('game')->with('success', 'Character has been re-rolled!');
    }
}
