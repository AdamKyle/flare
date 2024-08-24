<?php

namespace App\Admin\Exports\Affixes;

use App\Admin\Exports\Affixes\Sheets\AffixesSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AffixesExport implements WithMultipleSheets
{
    use Exportable;

    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new AffixesSheet($this->type);

        return $sheets;
    }
}
