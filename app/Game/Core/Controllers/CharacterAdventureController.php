<?php

namespace App\Game\Core\Controllers;


use App\Game\Core\Jobs\HandleAdventureRewards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Flare\Models\AdventureLog;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Adventures\View\AdventureCompletedRewards;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Adventures\Events\UpdateAdventureLogsBroadcastEvent;

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

        return view('game.adventures.completed-adventures', [
            'logs'      => $character->adventureLogs->load('adventure'),
            'character' => $character,
        ]);
    }

    public function completedAdventure(AdventureLog $adventureLog) {
        return view('game.adventures.completed-adventure', [
            'adventureLog' => $adventureLog,
            'character'    => auth()->user()->character,
        ]);
    }

    public function currentAdventure() {
        $character    = auth()->user()->character;

        $adventureLog = $character->adventureLogs()->find($character->current_adventure_id);

        if (is_null($adventureLog)) {
            return redirect()->to(route('game'))->with('error', 'You have no currently completed adventure. Check your completed adventures for more details.');
        }

        // Update the corresponding notification:
        $notification = $character->notifications()->where('adventure_id', $adventureLog->adventure->id)->where('read', false)->first();

        if (!is_null($notification)) {
            $notification->update([
                'read' => true,
            ]);
        }

        event(new UpdateAdventureLogsBroadcastEvent($character->refresh()->adventureLogs, $character->user));

        return view('game.adventures.current-adventure', [
            'log'          => $adventureLog->logs[array_key_last($adventureLog->logs)],
            'adventureLog' => $adventureLog,
            'character'    => $character->refresh(),
        ]);
    }

    public function collectReward(Request $request, AdventureLog $adventureLog) {

        $character = auth()->user()->character;
        $rewards   = $adventureLog->rewards;

        if (Cache::has('character-adventure-rewards-' . $character->id)) {
            return redirect()->to(route('game'))->with('error', 'You have to wait. We are processing the XP, Skill XP and Currencies. Once done, we\'ll hand off items so you can begin the next adventure.');
        }

        if (is_null($rewards)) {
            return redirect()->to(route('game'))->with('error', 'You cannot collect already collected rewards.');
        }

        $rewards = AdventureCompletedRewards::CombineRewards($rewards, $character);

        HandleAdventureRewards::dispatch($character, $adventureLog, $rewards)->delay(now()->addSeconds(10));

        return redirect()->to(route('game'))->with('success', 'Adventure Rewards are processing. Keep an eye on chat to see the rewards come through. 
        Once all rewards have been handed to you, you will be able to start a new adventure. Processing will begin in 10 seconds. You\'ll be able to embark on a new adventure when 
        the menu icon stops bouncing. You do not need to re-collect rewards - everything will update for you in real time.');
    }

    public function delete(AdventureLog $adventureLog) {
        if ($adventureLog->in_progress) {
            return redirect()->back()->with('error', 'Cannot delete log currently in progress.');
        }

        $adventureLog->delete();

        return redirect()->route('game.completed.adventures')->with('success', 'Log deleted.');
    }

    public function batchDelete(Request $request) {
        $logs = AdventureLog::findMany($request->logs);

        if ($logs->isEmpty()) {
            return redirect()->back()->with('error', 'No logs exist for selected.');
        }

        foreach ($logs as $log) {
            if (!$log->in_progress) {
                $log->delete();
            }
        }

        return redirect()->route('game.completed.adventures')->with('success', 'Selected logs have been deleted, with the exception of currently running adventure logs.');
    }
}
