<?php

namespace App\Admin\Import\ItemSkills;


use App\Admin\Import\ItemSkills\Sheets\ItemSkillsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ItemSkillsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new ItemSkillsSheet(),
        ];
    }
}
