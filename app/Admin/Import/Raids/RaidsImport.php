<?php

namespace App\Admin\Import\Raids;

use App\Admin\Import\Raids\Sheets\RaidSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RaidsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new RaidSheet(),
        ];
    }
}
