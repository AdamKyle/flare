<?php

namespace App\Admin\Exports\Affixes;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Affixes\Sheets\AffixesSheet;

class AffixesExport implements WithMultipleSheets {

    use Exportable;

    private $type;

    public function __construct(string $type) {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new AffixesSheet($this->type);

        return $sheets;
    }
}
