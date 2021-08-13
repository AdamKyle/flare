<?php

namespace App\Admin\Exports\Items;


use App\Admin\Exports\Items\Sheets\ItemsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Items\Sheets\QuestsSheet;

class ItemsExport implements WithMultipleSheets {

    use Exportable;

    private bool $affixesOnly = false;

    public function __construct(bool $affixesOnly) {
        $this->affixesOnly = $affixesOnly;
    }

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new ItemsSheet($this->affixesOnly);

        return $sheets;
    }
}
