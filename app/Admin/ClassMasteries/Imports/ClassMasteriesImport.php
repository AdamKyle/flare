<?php

namespace App\Admin\ClassMasteries\Imports;

use App\Admin\ClassMasteries\Imports\Sheets\ClassMasteriesSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassMasteriesImport implements Import, WithMultipleSheets
{
    /**
     * Return the sheets included in the Class Masteries workbook.
     */
    public function sheets(): array
    {
        return [
            0 => new ClassMasteriesSheet,
        ];
    }
}
