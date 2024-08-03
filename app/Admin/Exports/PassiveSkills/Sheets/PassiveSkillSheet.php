<?php

namespace App\Admin\Exports\PassiveSkills\Sheets;

use App\Flare\Models\PassiveSkill;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class PassiveSkillSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.passive-skills.sheets.passive-skills', [
            'passiveSkills' => PassiveSkill::all(),
        ]);
    }

    public function title(): string
    {
        return 'Passive Skills';
    }
}
