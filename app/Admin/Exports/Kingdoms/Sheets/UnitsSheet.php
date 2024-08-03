<?php

namespace App\Admin\Exports\Kingdoms\Sheets;

use App\Flare\Models\GameUnit;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class UnitsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.kingdoms.sheets.units', [
            'units' => GameUnit::all(),
        ]);
    }

    public function title(): string
    {
        return 'Units';
    }
}
