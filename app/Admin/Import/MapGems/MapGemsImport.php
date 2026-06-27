<?php

namespace App\Admin\Import\MapGems;

use App\Admin\Import\MapGems\Sheets\MapGemsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MapGemsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new MapGemsSheet,
        ];
    }
}
