<?php

namespace App\Admin\Exports\Items;

use App\Admin\Exports\Items\Sheets\ItemsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ItemsExport implements WithMultipleSheets {

    use Exportable;

    private array $itemTypes;

    public function __construct(array $itemTypes) {
        $this->itemTypes = $itemTypes;
    }

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new ItemsSheet($this->itemTypes);

        return $sheets;
    }
}
