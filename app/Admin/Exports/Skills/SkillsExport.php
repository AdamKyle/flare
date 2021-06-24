<?php

namespace App\Admin\Exports\Skills;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Skills\Sheets\SkillsSheet;

class SkillsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new SkillsSheet;

        return $sheets;
    }
}
