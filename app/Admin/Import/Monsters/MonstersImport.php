<?php

namespace App\Admin\Import\Monsters;

use App\Admin\Import\Monsters\Sheets\MonstersSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonstersImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new MonstersSheet,
        ];
    }
}
