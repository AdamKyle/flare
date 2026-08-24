<?php

namespace App\Admin\Import\LocationTemplates;

use App\Admin\Import\LocationTemplates\Sheets\LocationTemplatesSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationTemplatesImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new LocationTemplatesSheet,
        ];
    }
}
