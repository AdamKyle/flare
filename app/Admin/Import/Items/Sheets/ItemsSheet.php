<?php

namespace App\Admin\Import\Items\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameBuilding;

class ItemsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $items = array_combine($rows[0]->toArray(), $row->toArray());

                dd($items);
            }
        }
    }
}
