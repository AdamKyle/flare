<?php

namespace App\Admin\Exports\GuideQuests\Sheets;

use App\Flare\Models\GuideQuestWalkthroughStep;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class GuideQuestWalkthroughStepsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Return the ordered walkthrough-step export view.
     *
     * @return View
     */
    public function view(): View
    {
        return view('admin.exports.guide-quests.sheets.guide-quest-walkthrough-steps', [
            'steps' => GuideQuestWalkthroughStep::with('guideQuest')->orderBy('guide_quest_id')->orderBy('position')->get(),
        ]);
    }

    /**
     * Return the workbook sheet title.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Guide Quest Walkthrough Steps';
    }
}
