<?php

namespace App\Admin\Exports\Quests\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\Quest;

class QuestsSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {

        return view('admin.exports.quests.sheets.quests', [
            'quests' => Quest::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Quests';
    }
}
