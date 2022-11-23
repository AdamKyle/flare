<?php

namespace App\Admin\Exports\Npcs;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Npcs\Sheets\NpcsSheet;

class NpcsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new NpcsSheet;

        return $sheets;
    }
}
