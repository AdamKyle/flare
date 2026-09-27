<?php

namespace App\Admin\Skills\Imports\Sheets;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SkillsSheet implements ToCollection
{
    /**
     * Import game skill rows from the uploaded spreadsheet and persist them.
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
            $this->importSkill(array_combine($headers, $row->toArray()));
        });
    }

    /**
     * Create or update the Skill described by a single spreadsheet row.
     *
     * @param array $skill
     * @return void
     */
    private function importSkill(array $skill): void
    {
        $skill = $this->resolveGameClass($skill);
        $skill['can_train'] = $skill['can_train'] ?? false;
        $skill['is_locked'] = $skill['is_locked'] ?? false;

        $foundSkill = GameSkill::find($skill['id']);

        if (is_null($foundSkill)) {
            GameSkill::create($skill);

            return;
        }

        $foundSkill->update($skill);
    }

    /**
     * Keep the row's Class only when it references an existing Class.
     *
     * @param array $skill
     * @return array
     */
    private function resolveGameClass(array $skill): array
    {
        if (is_null($skill['game_class_id'])) {
            return $skill;
        }

        if (GameClass::where('id', $skill['game_class_id'])->exists()) {
            return $skill;
        }

        unset($skill['game_class_id']);

        return $skill;
    }
}
