<?php

namespace App\Admin\GameMaps\Exports;

use App\Admin\GameMaps\Exports\Sheets\GameMapsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GameMapsExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Game Maps workbook.
     */
    public function sheets(): array
    {
        return [
            new GameMapsSheet,
        ];
    }
}
