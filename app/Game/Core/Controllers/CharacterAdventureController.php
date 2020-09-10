<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Adventure;
use App\Flare\Models\AdventureLog;
use App\Flare\Models\Skill;
use App\Http\Controllers\Controller;
use App\Game\Core\Requests\TrainSkillValidation;
use App\Game\Core\Services\AdventureRewardService;
use Illuminate\Http\Request;

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
            'completedAdventures' => $character->adventureLogs->where('completed', true),
            'failedAdventures'    => $character->adventureLogs->where('completed', false),
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

        $messages  = $adventureRewardService->distributeRewards($rewards, $character)->getMessages();
        
        $adventureLog->update([
            'rewards' => null,
        ]);

        return redirect()->to(route('game'))->with('success', $messages);
    }
}
