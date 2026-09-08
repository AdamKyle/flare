<?php

namespace App\Admin\Items\Imports;

use App\Admin\Items\Imports\Sheets\ItemsSheet;
use App\Admin\Items\Services\ItemService;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ItemsImport implements Import, WithMultipleSheets
{
    /**
     * Return the sheets included in the Items workbook.
     */
    public function sheets(): array
    {
        return [
            0 => new ItemsSheet(new ItemService),
        ];
    }
}
