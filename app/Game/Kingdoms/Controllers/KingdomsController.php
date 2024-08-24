<?php

namespace App\Game\Kingdoms\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Events\UpdateKingdomLogs;
use App\Game\Kingdoms\Service\KingdomLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KingdomsController extends Controller
{
    private $kingdomLogService;

    public function __construct(KingdomLogService $kingdomLogService)
    {
        $this->kingdomLogService = $kingdomLogService;
    }

    public function attackLogs(Character $character)
    {
        $logs = $character->kingdomAttackLogs()->where('published', true)->get();

        Notification::where('type', 'kingdom')->where('character_id', $character->id)->delete();

        event(new UpdateKingdomLogs($character));

        return view('game.kingdoms.attack-logs', [
            'logs' => $logs,
            'character' => $character,
        ]);
    }

    public function attackLog(Character $character, KingdomLog $kingdomLog)
    {
        $kingdomLog->update([
            'opened' => true,
        ]);

        $kingdomLog = $kingdomLog->refresh();

        Notification::where('type', 'kingdom')->where('character_id', $character->id)->delete();

        event(new UpdateKingdomLogs($character));

        return view('game.kingdoms.attack-log', [
            'log' => $this->kingdomLogService->setLog($kingdomLog)->attackReport(),
            'type' => $kingdomLog->status,
            'character' => $character,
        ]);
    }

    public function batchDeleteLogs(Request $request, Character $character)
    {
        $logs = KingdomLog::findMany($request->logs);

        if ($logs->isEmpty()) {
            return redirect()->back()->with('error', 'No log exists for your selection.');
        }

        foreach ($logs as $log) {
            $log->delete();
        }

        return redirect()->route('game.kingdom.attack-logs', [
            'character' => $character,
        ])->with('success', 'Selected logs have been deleted.');
    }

    public function deleteLog(Character $character, KingdomLog $kingdomLog)
    {
        $name = $kingdomLog->status;

        $kingdomLog->delete();

        return redirect()->route('game.kingdom.attack-logs', [
            'character' => $character,
        ])->with('success', 'Deleted log: '.$name);
    }

    public function unitMovement(Character $character)
    {
        return view('game.kingdoms.unit_movement', [
            'character' => $character,
        ]);
    }
}
