<?php

namespace App\Game\Core\Controllers;


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

    public function collectReward(Request $request, AdventureRewardService $adventureRewardService, AdventureLog $adventureLog) {

        $character = auth()->user()->character;
        $rewards   = $adventureLog->rewards;

        if (is_null($rewards)) {
            return redirect()->to(route('game'))->with('error', 'You cannot collect already collected rewards.');
        }

        $rewards = AdventureCompletedRewards::CombineRewards($rewards, $character);

        $adventureRewardService = $adventureRewardService->distributeRewards($rewards, $character, $adventureLog);
        $messages               = $adventureRewardService->getMessages();

        $adventureLog->update([
            'rewards' => null,
        ]);

        $character->update([
            'current_adventure_id' => null,
        ]);

        $character = $character->refresh();

        event(new UpdateAdventureLogsBroadcastEvent($character->adventureLogs, $character->user));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        event(new UpdateTopBarEvent($character));

        $messages[] = 'You are a ready for your next adventure!';

        Cache::put('messages-' . $adventureLog->id, $messages);

        return redirect()->to(route('game'))->with('collected-rewards', $adventureLog->id);
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
