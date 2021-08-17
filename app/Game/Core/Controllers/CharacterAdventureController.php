<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Flare\Models\AdventureLog;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;
use App\Http\Controllers\Controller;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Adventures\Events\UpdateAdventureLogsBroadcastEvent;

use function PHPUnit\Framework\isEmpty;

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
        ]);
    }

    public function completedAdventureLogs(AdventureLog $adventureLog, string $name) {
        if (!isset($adventureLog->logs[$name])) {
            return redirect()->back()->with('error', 'Invalid input.');
        }

        return view('game.adventures.current-adventure', [
            'log'          => $adventureLog->logs[$name],
            'adventureLog' => $adventureLog
        ]);
    }

    public function currentAdventure() {
        $character = auth()->user()->character;

        $adventureLog = $character->adventureLogs()->orderBy('id', 'desc')->first();

        if (is_null($adventureLog)) {
            return redirect()->back()->with('error', 'You have no currently completed adventure. Check your completed adventures for more details.');
        }

        // Update the coresponding notification:
        $notification = $character->notifications()->where('adventure_id', $adventureLog->adventure->id)->where('read', false)->first();

        if (!is_null($notification)) {
            $notification->update([
                'read' => true,
            ]);
        }

        if (is_null($adventureLog->rewards)) {
            event(new UpdateAdventureLogsBroadcastEvent($character->refresh()->adventureLogs, $character->user));
        }

        return view('game.adventures.current-adventure', [
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

        $adventureRewardService = $adventureRewardService->distributeRewards($rewards, $character);
        $messages               = $adventureRewardService->getMessages();

        if (array_key_exists('error', $messages)) {
            $rewards['xp'] = 0;

            if (isset($rewards['skill'])) {
                $rewards['skill']['exp'] = 0;
            }

            $rewards['items'] = $adventureRewardService->getItemsLeft();

            $adventureLog->update([
                'rewards' => $rewards,
            ]);

            return redirect()->back()->with('error', $messages['error']);
        } else {
            $adventureLog->update([
                'rewards' => null,
            ]);
        }

        event(new UpdateAdventureLogsBroadcastEvent($character->refresh()->adventureLogs, $character->user));

        if (empty($messages)) {
            $messages = [
                'You are a ready for your next adventure!'
            ];
        }

        return redirect()->to(route('game'))->with('success', $messages);
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
