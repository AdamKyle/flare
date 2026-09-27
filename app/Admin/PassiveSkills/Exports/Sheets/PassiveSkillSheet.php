<?php

namespace App\Admin\PassiveSkills\Exports\Sheets;

use App\Flare\Models\PassiveSkill;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class PassiveSkillSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Render the Passive Skills sheet.
     *
     * @return View
     */
    public function view(): View
    {
        return view('admin.exports.passive-skills.sheets.passive-skills', [
            'passiveSkills' => PassiveSkill::all(),
        ]);
    }

    /**
     * Name the Passive Skills sheet.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Passive Skills';
    }
}
