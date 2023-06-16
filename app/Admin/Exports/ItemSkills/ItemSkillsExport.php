<?php

namespace App\Admin\Exports\ItemSkills;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\ItemSkills\Sheets\ItemSkillsSheet;

class ItemSkillsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new ItemSkillsSheet;

        return $sheets;
    }
}
