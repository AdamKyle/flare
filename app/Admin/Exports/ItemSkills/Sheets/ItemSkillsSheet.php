<?php

namespace App\Admin\Exports\ItemSkills\Sheets;

use App\Flare\Models\ItemSkill;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class ItemSkillsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.item-skills.sheets.skill', [
            'skills' => ItemSkill::all(),
        ]);
    }

    public function title(): string
    {
        return 'Item Skills';
    }
}
