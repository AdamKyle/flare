<?php

namespace App\Admin\Import\Monsters;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Monsters\Sheets\MonstersSheet;

class MonstersImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new MonstersSheet,
        ];
    }
}
