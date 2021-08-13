<?php

namespace App\Admin\Import\Npcs;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Npcs\Sheets\NpcCommandsSheet;
use App\Admin\Import\Npcs\Sheets\NpcsSheet;

class NpcsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new NpcsSheet,
            1 => new NpcCommandsSheet,
        ];
    }
}
