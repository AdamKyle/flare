<?php

namespace App\Admin\Import\Races\Sheets;

use App\Flare\Models\GameRace;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RacesSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $gameRace = array_combine($rows[0]->toArray(), $row->toArray());

                $foundRace = GameRace::where('name', $gameRace['name'])->first();

                if (is_null($foundRace)) {
                    GameRace::create($gameRace);
                }
            }
        }
    }

}
