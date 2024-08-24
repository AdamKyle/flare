<?php

namespace App\Admin\Import\ItemSkills\Sheets;

use App\Flare\Models\Character;
use App\Flare\Models\ItemSkill;
use App\Game\Character\Builders\AttackBuilders\Jobs\CreateCharacterAttackData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ItemSkillsSheet implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $skill = array_combine($rows[0]->toArray(), $row->toArray());

                if (isset($skill['parent_id'])) {
                    $parentSkill = ItemSkill::where('name', $skill['parent_id'])->first();

                    if (! is_null($parentSkill)) {
                        $skill['parent_id'] = $parentSkill->id;
                    } else {
                        $skill['parent_id'] = null;
                        $skill['parent_level_needed'] = null;
                    }
                }

                $exisitingSkill = ItemSkill::where('name', $skill['name'])->first();

                if (is_null($exisitingSkill)) {
                    ItemSkill::create($skill);

                    continue;
                }

                $exisitingSkill->update($skill);
            }
        }

        Character::chunkById(100, function ($characters) {
            foreach ($characters as $character) {
                CreateCharacterAttackData::dispatch($character->id)->onConnection('long_running');
            }
        });
    }
}
