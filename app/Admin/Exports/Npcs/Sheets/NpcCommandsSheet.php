<?php

namespace App\Admin\Exports\Npcs\Sheets;

use App\Flare\Models\Npc;
use App\Flare\Models\NpcCommand;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class NpcCommandsSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.npcs.sheets.commands', [
            'commands' => NpcCommand::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Npcs Commands';
    }
}
