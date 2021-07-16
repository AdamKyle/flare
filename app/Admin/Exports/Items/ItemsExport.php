<?php

namespace App\Admin\Exports\Items;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Items\Sheets\ItemsSheet;

class ItemsExport implements WithMultipleSheets {

    use Exportable;

    private Boolean $affixesOnly = false;

    public function affixesOnly() {
        $this->affixesOnly = true;
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
