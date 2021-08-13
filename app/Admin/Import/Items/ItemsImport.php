<?php

namespace App\Admin\Import\Items;


use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Items\Sheets\ItemsSheet;

class ItemsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new ItemsSheet(),
        ];
    }
}
