<?php

namespace App\Admin\Exports\LocationTemplates;

use App\Admin\Exports\LocationTemplates\Sheets\LocationTemplatesSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationTemplatesExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new LocationTemplatesSheet,
        ];
    }
}
