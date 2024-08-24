<?php

namespace App\Admin\Exports\Quests;

use App\Admin\Exports\Quests\Sheets\QuestsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QuestsExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new QuestsSheet;

        return $sheets;
    }
}
