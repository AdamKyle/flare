<?php

namespace App\Admin\Exports\Skills\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\GameSkill;

class SkillsSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.skills.sheets.skills', [
            'skills' => GameSkill::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Game Skills';
    }
}
