<?php

namespace App\Admin\LocationGems\Exports;

use App\Admin\LocationGems\Exports\Sheets\LocationGemsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationGemsExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Location Gems workbook.
     *
     * @return array<int, LocationGemsSheet> Location Gems workbook sheets.
     */
    public function sheets(): array
    {
        return [
            new LocationGemsSheet,
        ];
    }
}
