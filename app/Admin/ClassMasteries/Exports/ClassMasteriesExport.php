<?php

namespace App\Admin\ClassMasteries\Exports;

use App\Admin\ClassMasteries\Exports\Sheets\ClassMasteriesSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassMasteriesExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Class Masteries workbook.
     */
    public function sheets(): array
    {
        return [
            new ClassMasteriesSheet,
        ];
    }
}
