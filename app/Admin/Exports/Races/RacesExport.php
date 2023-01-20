<?php

namespace App\Admin\Exports\Races;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Races\Sheets\RacesSheet;

class RacesExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new RacesSheet;

        return $sheets;
    }
}
