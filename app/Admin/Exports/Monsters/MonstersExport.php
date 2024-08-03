<?php

namespace App\Admin\Exports\Monsters;

use App\Admin\Exports\Monsters\Sheets\MonstersSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonstersExport implements WithMultipleSheets
{
    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new MonstersSheet($this->type);

        return $sheets;
    }
}
