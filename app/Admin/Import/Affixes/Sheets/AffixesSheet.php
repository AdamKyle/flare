<?php

namespace App\Admin\Import\Affixes\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;

class AffixesSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $affix = array_combine($rows[0]->toArray(), $row->toArray());

                $affixData = $this->returnCleanAffix($affix);

                if (is_null($affixData)) {
                    continue;
                } else {
                    $foundAffix = ItemAffix::find($affixData['id']);

                    if (!is_null($foundAffix)) {
                        $foundAffix->update($affixData);
                    } else {
                        ItemAffix::create($affixData);
                    }
                }
            }
        }
    }

    protected function returnCleanAffix(array $item) {
        $cleanData = [];

        if (!isset($item['can_drop'])) {
            $item['can_drop'] = false;
        }

        if (!isset($item['damage_can_stack'])) {
            $item['damage_can_stack'] = false;
        }

        if (!isset($item['irresistible_damage'])) {
            $item['irresistible_damage'] = false;
        }

        if (!isset($item['reduces_enemy_stats'])) {
            $item['reduces_enemy_stats'] = false;
        }

        foreach ($item as $key => $value) {
            if (!is_null($value)) {
                if ($key === 'skill_name') {
                    $foundSkill = GameSkill::where('name', $value)->first();

                    if (is_null($foundSkill)) {
                        return null;
                    }
                }

                $cleanData[$key] = $value;
            }
        }

        return $cleanData;
    }
}
