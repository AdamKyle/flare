<?php

namespace App\Admin\Exports\GuideQuests;

use App\Admin\Exports\GuideQuests\Sheets\GuideQuestSheet;
use App\Admin\Exports\GuideQuests\Sheets\GuideQuestWalkthroughStepsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GuideQuestsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new GuideQuestSheet;
        $sheets[] = new GuideQuestWalkthroughStepsSheet;

        return $sheets;
    }
}
