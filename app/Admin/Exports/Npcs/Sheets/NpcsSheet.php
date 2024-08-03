<?php

namespace App\Admin\Exports\Npcs\Sheets;

use App\Flare\Models\Npc;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class NpcsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.npcs.sheets.npcs', [
            'npcs' => Npc::all(),
        ]);
    }

    public function title(): string
    {
        return 'NPCs';
    }
}
