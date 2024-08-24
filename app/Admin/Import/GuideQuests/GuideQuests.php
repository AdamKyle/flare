<?php

namespace App\Admin\Import\GuideQuests;

use App\Admin\Import\GuideQuests\Sheets\GuideQuestsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GuideQuests implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new GuideQuestsSheet,
        ];
    }
}
