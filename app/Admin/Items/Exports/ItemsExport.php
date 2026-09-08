<?php

namespace App\Admin\Items\Exports;

use App\Admin\Items\Exports\Sheets\ItemsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ItemsExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly array $itemTypes = []) {}

    /**
     * Return the sheets included in the Items family workbook.
     */
    public function sheets(): array
    {
        return [
            new ItemsSheet($this->itemTypes),
        ];
    }
}
