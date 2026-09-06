<?php

namespace App\Admin\LocationGems\Imports;

use App\Admin\LocationGems\Imports\Sheets\LocationGemsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationGemsImport implements Import, WithMultipleSheets
{
    /**
     * Return the sheets included in the Location Gems workbook.
     *
     * @return array<int, LocationGemsSheet> Location Gems workbook sheets.
     */
    public function sheets(): array
    {
        return [
            0 => new LocationGemsSheet,
        ];
    }
}
