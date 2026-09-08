<?php

namespace App\Admin\Classes\Imports;

use App\Admin\Classes\Imports\Sheets\ClassesSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassesImport implements Import, WithMultipleSheets
{
    /**
     * Return the sheets included in the Classes workbook.
     */
    public function sheets(): array
    {
        return [
            0 => new ClassesSheet,
        ];
    }
}
