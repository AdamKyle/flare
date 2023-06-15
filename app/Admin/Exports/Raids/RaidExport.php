<?php

namespace App\Admin\Exports\Raids;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Raids\Sheets\RaidSheet;

class RaidExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new RaidSheet();

        return $sheets;
    }
}
