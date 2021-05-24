<?php

namespace App\Admin\Import\Affixes;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Affixes\Sheets\AffixesSheet;

class AffixesImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new AffixesSheet,
        ];
    }
}
