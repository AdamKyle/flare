<?php

namespace App\Admin\Import\Events;

use App\Admin\Import\Events\Sheets\EventsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EventsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new EventsSheet,
        ];
    }
}
