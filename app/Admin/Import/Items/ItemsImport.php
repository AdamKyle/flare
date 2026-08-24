<?php

namespace App\Admin\Import\Items;

use App\Admin\Import\Items\Sheets\ItemsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ItemsImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new ItemsSheet,
        ];
    }
}
