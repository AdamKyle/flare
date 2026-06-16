<?php

namespace App\Admin\Exports\LocationGems;

use App\Admin\Exports\LocationGems\Sheets\LocationGemsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationGemsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new LocationGemsSheet,
        ];
    }
}
