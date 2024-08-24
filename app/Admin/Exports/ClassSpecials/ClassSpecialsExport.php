<?php

namespace App\Admin\Exports\ClassSpecials;

use App\Admin\Exports\ClassSpecials\Sheets\ClassSpecialsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassSpecialsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new ClassSpecialsSheet;

        return $sheets;
    }
}
