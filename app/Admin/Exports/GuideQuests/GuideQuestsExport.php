<?php

namespace App\Admin\Exports\GuideQuests;

use App\Admin\Exports\GuideQuests\Sheets\GuideQuestSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GuideQuestsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new GuideQuestSheet();

        return $sheets;
    }
}
