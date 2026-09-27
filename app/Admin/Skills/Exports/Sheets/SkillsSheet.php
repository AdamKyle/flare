<?php

namespace App\Admin\Skills\Exports\Sheets;

use App\Flare\Models\GameSkill;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class SkillsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Render the Game Skills sheet.
     *
     * @return View
     */
    public function view(): View
    {
        return view('admin.exports.skills.sheets.skills', [
            'skills' => GameSkill::all(),
        ]);
    }

    /**
     * Name the Game Skills sheet.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Game Skills';
    }
}
