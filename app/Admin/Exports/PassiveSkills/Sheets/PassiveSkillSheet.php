<?php

namespace App\Admin\Exports\PassiveSkills\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\PassiveSkill;

class PassiveSkillSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.passive-skills.sheets.passive-skills', [
            'passiveSkills' => PassiveSkill::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Passive Skills';
    }
}
