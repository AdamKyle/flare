<?php

namespace App\Admin\Quests\Exports;

use App\Admin\Quests\Exports\Sheets\QuestsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QuestsExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Quests workbook.
     *
     * @return array<int, QuestsSheet> Quests workbook sheets.
     */
    public function sheets(): array
    {
        return [
            new QuestsSheet,
        ];
    }
}
