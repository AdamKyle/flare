<?php

namespace App\Admin\Import\Classes;

use App\Admin\Import\Classes\Sheets\ClassSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new ClassSheet,
        ];
    }
}
