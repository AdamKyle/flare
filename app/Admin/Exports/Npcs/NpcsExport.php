<?php

namespace App\Admin\Exports\Npcs;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Npcs\Sheets\NpcsSheet;
use App\Admin\Exports\Npcs\Sheets\NpcCommandsSheet;

class NpcsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new NpcsSheet;
        $sheets[] = new NpcCommandsSheet;

        return $sheets;
    }
}
