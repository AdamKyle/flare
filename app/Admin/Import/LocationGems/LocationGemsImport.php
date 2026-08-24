<?php

namespace App\Admin\Import\LocationGems;

use App\Admin\Import\LocationGems\Sheets\LocationGemsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationGemsImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new LocationGemsSheet,
        ];
    }
}
