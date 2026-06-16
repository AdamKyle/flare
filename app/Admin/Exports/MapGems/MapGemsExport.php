<?php

namespace App\Admin\Exports\MapGems;

use App\Admin\Exports\MapGems\Sheets\MapGemsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MapGemsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new MapGemsSheet,
        ];
    }
}
