<?php

namespace App\Admin\Import\ClassSpecials;

use App\Admin\Import\ClassSpecials\Sheets\ClassSpecialsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassSpecialsImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new ClassSpecialsSheet,
        ];
    }
}
