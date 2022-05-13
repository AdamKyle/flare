<?php

namespace App\Admin\Exports\Monsters;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Monsters\Sheets\MonstersSkillsSheet;
use App\Admin\Exports\Monsters\Sheets\MonstersSheet;

class MonstersExport implements WithMultipleSheets {

    private string $type;

    public function __construct(string $type) {
        $this->type = $type;
    }

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new MonstersSheet($this->type);

        return $sheets;
    }
}
