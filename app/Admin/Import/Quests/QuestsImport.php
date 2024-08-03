<?php

namespace App\Admin\Import\Quests;

use App\Admin\Import\Quests\Sheets\QuestsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QuestsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new QuestsSheet,
        ];
    }
}
