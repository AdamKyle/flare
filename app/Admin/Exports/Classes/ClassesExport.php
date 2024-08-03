<?php

namespace App\Admin\Exports\Classes;

use App\Admin\Exports\Classes\Sheets\ClassesSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassesExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new ClassesSheet;

        return $sheets;
    }
}
