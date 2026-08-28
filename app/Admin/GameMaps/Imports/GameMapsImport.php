<?php

namespace App\Admin\GameMaps\Imports;

use App\Admin\GameMaps\Imports\Sheets\GameMapsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GameMapsImport implements Import, WithMultipleSheets
{
    /**
     * Map the first workbook sheet to the Game Maps settings importer.
     *
     * @return array<int, GameMapsSheet>
     */
    public function sheets(): array
    {
        return [
            0 => new GameMapsSheet,
        ];
    }
}
