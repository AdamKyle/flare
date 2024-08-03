<?php

namespace App\Admin\Import\Classes\Sheets;

use App\Flare\Models\GameClass;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ClassSheet implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $gameClass = array_combine($rows[0]->toArray(), $row->toArray());

                $foundClass = GameClass::where('name', $gameClass['name'])->first();

                if (is_null($foundClass)) {

                    if (! is_null($gameClass['primary_required_class_id'])) {
                        $primaryClass = GameClass::where('name', $gameClass['primary_required_class_id'])->first();

                        if (is_null($primaryClass)) {
                            continue;
                        }

                        $gameClass['primary_required_class_id'] = $primaryClass->id;
                    }

                    if (! is_null($gameClass['secondary_required_class_id'])) {
                        $secondaryClass = GameClass::where('name', $gameClass['secondary_required_class_id'])->first();

                        if (is_null($secondaryClass)) {
                            continue;
                        }

                        $gameClass['secondary_required_class_id'] = $secondaryClass->id;
                    }

                    GameClass::create($gameClass);
                }
            }
        }
    }
}
