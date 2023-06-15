<?php

namespace App\Admin\Exports\Raids\Sheets;

use App\Flare\Models\Raid;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RaidSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.raids.sheets.raid', [
            'raids' => Raid::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Raids';
    }
}
