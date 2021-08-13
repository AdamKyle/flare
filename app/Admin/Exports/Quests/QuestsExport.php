<?php

namespace App\Admin\Exports\Quests;


use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Quests\Sheets\QuestsSheet;

class QuestsExport implements WithMultipleSheets {

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new QuestsSheet;

        return $sheets;
    }
}
