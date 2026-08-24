<?php

namespace App\Admin\Import\Raids;

use App\Admin\Import\Raids\Sheets\RaidSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RaidsImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new RaidSheet,
        ];
    }
}
