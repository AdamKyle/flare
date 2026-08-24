<?php

namespace App\Admin\Import\Classes;

use App\Admin\Import\Classes\Sheets\ClassSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new ClassSheet,
        ];
    }
}
