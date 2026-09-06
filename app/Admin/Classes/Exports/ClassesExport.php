<?php

namespace App\Admin\Classes\Exports;

use App\Admin\Classes\Exports\Sheets\ClassesSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassesExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Classes workbook.
     *
     * @return array<int, ClassesSheet> Classes workbook sheets.
     */
    public function sheets(): array
    {
        return [
            new ClassesSheet,
        ];
    }
}
