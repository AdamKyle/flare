<?php

namespace App\Admin\Exports\GuideQuests;

use App\Admin\Exports\GuideQuests\Sheets\GuideQuestSheet;
use App\Admin\Exports\GuideQuests\Sheets\GuideQuestWalkthroughStepsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GuideQuestsExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * Return the Guide Quest workbook sheets in import order.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            new GuideQuestSheet,
            new GuideQuestWalkthroughStepsSheet,
        ];
    }
}
