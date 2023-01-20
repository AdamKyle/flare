<?php

namespace App\Admin\Import\Races;


use App\Admin\Import\Races\Sheets\RacesSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RacesImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new RacesSheet(),
        ];
    }
}
