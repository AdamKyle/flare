<?php

namespace App\Admin\Import\GuideQuests;

use App\Admin\Import\GuideQuests\Sheets\GuideQuestsSheet;
use App\Admin\Import\GuideQuests\Sheets\GuideQuestWalkthroughStepsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GuideQuests implements Import, WithMultipleSheets
{
    /**
     * Return Guide Quest workbook sheets in dependency order.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            0 => new GuideQuestsSheet,
            1 => new GuideQuestWalkthroughStepsSheet,
        ];
    }
}
