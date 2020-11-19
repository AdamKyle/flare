<?php

namespace App\Game\Core\Controllers;

use Illuminate\Http\Request;
use App\Flare\Models\AdventureLog;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;
use App\Http\Controllers\Controller;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Maps\Adventure\Events\UpdateAdventureLogsBroadcastEvent;

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

        return view('game.core.character.completed-adventures', [
            'logs' => $character->adventureLogs->load('adventure'),
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

        if (empty($messages)) {
            $messages = [
                'You are a ready for your next adventure!'
            ];
        }
        
        return redirect()->to(route('game'))->with('success', $messages);
    }
}
