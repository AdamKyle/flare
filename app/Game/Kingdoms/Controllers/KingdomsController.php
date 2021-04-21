<?php

namespace App\Game\Kingdoms\Controllers;

use App\Flare\Models\KingdomLog;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use Illuminate\Http\Request;

class KingdomsController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function attackLogs(Character $character) {
        $logs = $character->kingdomAttackLogs;

        return view('game.kingdoms.attack-logs', [
            'logs'      => $logs,
            'character' => $character,
        ]);
    }

    public function batchDeleteLogs(Request $request, Character $character) {
        $logs = KingdomLog::findMany($request->logs);

        if ($logs->isEmpty()) {
            return redirect()->back()->with('error', 'No logs exist for selected.');
        }

        foreach ($logs as $log) {
            $log->delete();
        }

        return redirect()->route('game.kingdom.attack-logs', [
            'character' => $character
        ])->with('success', 'Selected logs have been deleted.');
    }

    public function deleteLog(Character $character, KingdomLog $kingdomLog) {
        $name = $kingdomLog->status;

        $kingdomLog->delete();

        return redirect()->route('game.kingdom.attack-logs', [
            'character' => $character
        ])->with('success', 'Deleted log: ' . $name);
    }
}
