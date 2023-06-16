<?php

namespace App\Admin\Exports\ItemSkills\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\ItemSkill;

class ItemSkillsSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.item-skills.sheets.skill', [
            'skills' => ItemSkill::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Item Skills';
    }
}
