<?php

namespace App\Admin\Exports\Classes;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Classes\Sheets\ClassesSheet;

class ClassesExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new ClassesSheet;

        return $sheets;
    }
}
