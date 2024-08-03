<?php

namespace App\Admin\Exports\Kingdoms\Sheets;

use App\Flare\Models\GameBuilding;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class BuildingsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.kingdoms.sheets.buildings', [
            'buildings' => GameBuilding::all(),
        ]);
    }

    public function title(): string
    {
        return 'Buildings';
    }
}
