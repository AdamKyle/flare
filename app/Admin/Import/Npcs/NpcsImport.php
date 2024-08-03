<?php

namespace App\Admin\Import\Npcs;

use App\Admin\Import\Npcs\Sheets\NpcsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NpcsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new NpcsSheet,
        ];
    }
}
