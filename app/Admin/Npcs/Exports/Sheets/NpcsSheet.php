<?php

namespace App\Admin\Npcs\Exports\Sheets;

use App\Flare\Models\Npc;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class NpcsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Build the export view with all current NPCs.
     *
     * @return View NPCs workbook export view.
     */
    public function view(): View
    {
        return view('admin.npcs.exports.sheets.npcs', [
            'npcs' => Npc::all(),
        ]);
    }

    /**
     * Return the NPCs workbook sheet title.
     *
     * @return string NPCs workbook sheet title.
     */
    public function title(): string
    {
        return 'NPCs';
    }
}
