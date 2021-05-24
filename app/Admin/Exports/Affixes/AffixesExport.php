<?php

namespace App\Admin\Exports\Affixes;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Affixes\Sheets\AffixesSheet;

class AffixesExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new AffixesSheet;

        return $sheets;
    }
}
