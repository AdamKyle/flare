<?php

namespace App\Admin\Import\ClassSpecials\Sheets;

use App\Flare\Models\GameClassSpecial;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ClassSpecialsSheet implements ToCollection
{
    /**
     * Import class special rows from the uploaded spreadsheet and persist them.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $classSpecial = array_combine($rows[0]->toArray(), $row->toArray());

                GameClassSpecial::updateOrCreate([
                    'name' => $classSpecial['name'],
                ], $classSpecial);
            }
        }
    }
}
