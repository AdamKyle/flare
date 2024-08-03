<?php

namespace App\Admin\Exports\Skills\Sheets;

use App\Flare\Models\GameSkill;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class SkillsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.skills.sheets.skills', [
            'skills' => GameSkill::all(),
        ]);
    }

    public function title(): string
    {
        return 'Game Skills';
    }
}
