<?php

namespace App\Admin\Exports\GuideQuests\Sheets;

use App\Flare\Models\GuideQuest;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class GuideQuestSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.guide-quests.sheets.guide-quests', [
            'guideQuests' => GuideQuest::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Guide Quests';
    }
}
