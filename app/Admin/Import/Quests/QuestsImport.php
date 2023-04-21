<?php

namespace App\Admin\Import\Quests;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Quests\Sheets\QuestsSheet;

class QuestsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new QuestsSheet,
        ];
    }
}
