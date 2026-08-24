<?php

namespace App\Admin\Import\GuideQuests;

use App\Admin\Import\GuideQuests\Sheets\GuideQuestsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GuideQuests implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new GuideQuestsSheet,
        ];
    }
}
