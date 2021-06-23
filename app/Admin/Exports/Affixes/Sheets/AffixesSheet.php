<?php

namespace App\Admin\Exports\Affixes\Sheets;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\ItemAffix;

class AffixesSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.affixes.sheets.affixes', [
            'affixes' => ItemAffix::orderBy('skill_level_required', 'asc')->get(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Affixes';
    }
}
