<?php

namespace App\Admin\Exports\Events;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Events\Sheets\EventsSheet;

class EventsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new EventsSheet();

        return $sheets;
    }
}
