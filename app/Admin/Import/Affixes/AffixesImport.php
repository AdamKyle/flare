<?php

namespace App\Admin\Import\Affixes;

use App\Admin\Import\Affixes\Sheets\AffixesSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AffixesImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new AffixesSheet,
        ];
    }
}
