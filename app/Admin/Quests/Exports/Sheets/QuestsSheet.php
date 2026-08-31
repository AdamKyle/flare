<?php

namespace App\Admin\Quests\Exports\Sheets;

use App\Flare\Models\Quest;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class QuestsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Build the export view containing every current Quest.
     *
     * @return View Quests workbook export view.
     */
    public function view(): View
    {
        $quests = Quest::with(['npc', 'item', 'raid', 'requiredQuest', 'rewardItem', 'secondaryItem', 'requiredPlane', 'factionMap', 'passive', 'factionLoyaltyNpc', 'parent'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $questNamesById = Quest::pluck('name', 'id');

        return view('admin.quests.exports.sheets.quests', [
            'quests' => $quests,
            'questNamesById' => $questNamesById,
        ]);
    }

    /**
     * Return the Quests workbook sheet title.
     *
     * @return string Quests workbook sheet title.
     */
    public function title(): string
    {
        return 'Quests';
    }
}
