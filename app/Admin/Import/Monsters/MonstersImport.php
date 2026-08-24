<?php

namespace App\Admin\Import\Monsters;

use App\Admin\Import\Monsters\Sheets\MonstersSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonstersImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new MonstersSheet,
        ];
    }
}
