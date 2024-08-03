<?php

namespace App\Admin\Exports\Raids\Sheets;

use App\Flare\Models\Raid;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class RaidSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.raids.sheets.raid', [
            'raids' => Raid::all(),
        ]);
    }

    public function title(): string
    {
        return 'Raids';
    }
}
