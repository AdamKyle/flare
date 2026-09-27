<?php

namespace App\Admin\Kingdoms\Imports\Sheets;

use App\Flare\Models\GameUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UnitsSheet implements ToCollection
{
    /**
     * Import kingdom unit rows from the uploaded spreadsheet and persist them.
     *
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $headers = $rows->first()->toArray();

        $rows->slice(1)->each(function (Collection $row) use ($headers): void {
            $unitData = array_filter(array_combine($headers, $row->toArray()), fn (mixed $value): bool => ! is_null($value));

            GameUnit::updateOrCreate(['id' => $unitData['id'] ?? null], $unitData);
        });
    }
}
