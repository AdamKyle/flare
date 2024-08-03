<?php

namespace App\Admin\Exports\Quests\Sheets;

use App\Flare\Models\Quest;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class QuestsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {

        return view('admin.exports.quests.sheets.quests', [
            'quests' => Quest::getAllQuestsInOrder(),
        ]);
    }

    public function title(): string
    {
        return 'Quests';
    }
}
