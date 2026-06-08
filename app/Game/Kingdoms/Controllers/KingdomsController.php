<?php

namespace App\Game\Kingdoms\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Events\UpdateKingdomLogs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KingdomsController extends Controller
{
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

    public function batchDeleteLogs(Request $request, Character $character)
    {
        $logs = $character->kingdomAttackLogs()->whereIn('id', $request->logs)->get();

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
        if ($kingdomLog->character_id !== $character->id) {
            return redirect()->back()->with('error', 'No log exists for your selection.');
        }

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
