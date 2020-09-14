<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Flare\Models\AdventureLog;
use App\Http\Controllers\Controller;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Maps\Adventure\Events\UpdateAdventureLogsBroadcastEvent;

class CharacterAdventureController extends Controller {

    public function __construct() {
        $this->middleware('auth');
        $this->middleware('is.character.dead')->only([
            'collectReward'
        ]);
        $this->middleware('is.character.adventuring')->only([
            'collectReward'
        ]);
    }

    public function completedAdventures() {
        $character = auth()->user()->character;

        return view('game.core.character.completed-adventures', [
            'adventures' => $character->adventureLogs,
        ]);
    }

    public function completedAdventure(AdventureLog $adventureLog) {
        return view('game.core.character.completed-adventure', [
            'adventureLog' => $adventureLog,
        ]);
    }

    public function completedAdventureLogs(AdventureLog $adventureLog, string $name) {
        if (!isset($adventureLog->logs[$name])) {
            return redirect()->back()->with('error', 'Invalid input.');
        }
        
        return view('game.core.character.current-adventure', [
            'log'          => $adventureLog->logs[$name],
            'adventureLog' => $adventureLog
        ]);
    }

    public function currentAdventure() {
        $character = auth()->user()->character;

        $adventureLog = $character->adventureLogs->filter(function($log) {
            return !is_null($log->rewards);
        })->first();
        
        if (is_null($adventureLog)) {
            return redirect()->back()->with('error', 'You have no currently completed adventure. Check your completed adventures for more details.');
        }
        
        return view('game.core.character.current-adventure', [
            'log'          => $adventureLog->logs[array_key_last($adventureLog->logs)],
            'adventureLog' => $adventureLog
        ]);
    }

    public function collectReward(Request $request, AdventureRewardService $adventureRewardService, AdventureLog $adventureLog) {
        $character = auth()->user()->character;
        $rewards   = $adventureLog->rewards;

        if (is_null($rewards)) {
            return redirect()->to(route('game'))->with('error', 'You cannot collect already collected rewards.');
        }

        $messages  = $adventureRewardService->distributeRewards($rewards, $character)->getMessages();
        
        $adventureLog->update([
            'rewards' => null,
        ]);

        event(new UpdateAdventureLogsBroadcastEvent($character->refresh()->adventureLogs, $character->user));


        return redirect()->to(route('game'))->with('success', $messages);
    }
}
