<?php

namespace App\Game\Kingdoms\Controllers;

use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Service\KingdomLogService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use Illuminate\Http\Request;

class KingdomsController extends Controller {

    /**
     * @var KingdomLogService $kingdomLogService
     */
    private $kingdomLogService;

    /**
     * KingdomsController constructor.
     *
     * @param KingdomLogService $kingdomLogService
     */
    public function __construct(KingdomLogService $kingdomLogService) {
        $this->middleware('auth');

        $this->kingdomLogService = $kingdomLogService;
    }

    public function attackLogs(Character $character) {
        $logs = $character->kingdomAttackLogs;

        return view('game.kingdoms.attack-logs', [
            'logs'      => $logs,
            'character' => $character,
        ]);
    }

    public function attackLog(Character $character, KingdomLog $kingdomLog) {
        return view('game.kingdoms.attack-log', [
            'log'       => $this->kingdomLogService->setLog($kingdomLog)->attackReport(),
            'type'      => $kingdomLog->status,
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
