<?php

namespace App\Admin\Import\Classes;


use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Classes\Sheets\ClassSheet;

class ClassImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new ClassSheet(),
        ];
    }
}
