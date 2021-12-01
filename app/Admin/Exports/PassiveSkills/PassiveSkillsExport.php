<?php

namespace App\Admin\Exports\PassiveSkills;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\PassiveSkills\Sheets\PassiveSkillSheet;

class PassiveSkillsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new PassiveSkillSheet;

        return $sheets;
    }
}
