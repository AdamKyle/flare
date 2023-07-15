<?php

namespace App\Admin\Exports\Locations;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Locations\Sheets\LocationsSheet;

class LocationsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new LocationsSheet;

        return $sheets;
    }
}
