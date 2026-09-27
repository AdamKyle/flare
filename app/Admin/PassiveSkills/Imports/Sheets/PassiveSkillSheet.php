<?php

namespace App\Admin\PassiveSkills\Imports\Sheets;

use App\Flare\Models\PassiveSkill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PassiveSkillSheet implements ToCollection
{
    /**
     * Import passive skill rows from the uploaded spreadsheet and persist them.
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
            $this->importPassiveSkill(array_combine($headers, $row->toArray()));
        });
    }

    /**
     * Create or update the Passive Skill described by a single spreadsheet row.
     *
     * @param array $passiveSkillData
     * @return void
     */
    private function importPassiveSkill(array $passiveSkillData): void
    {
        $passiveSkillData['is_locked'] = $passiveSkillData['is_locked'] ?? false;
        $passiveSkillData['is_parent'] = $passiveSkillData['is_parent'] ?? false;

        PassiveSkill::updateOrCreate(['id' => $passiveSkillData['id']], $this->resolveParentSkill($passiveSkillData));
    }

    /**
     * Keep the row's parent only when it references an existing Passive Skill.
     *
     * @param array $passiveSkillData
     * @return array
     */
    private function resolveParentSkill(array $passiveSkillData): array
    {
        if (is_null($passiveSkillData['parent_skill_id'] ?? null)) {
            return $passiveSkillData;
        }

        $passiveSkillData['parent_skill_id'] = PassiveSkill::where('id', $passiveSkillData['parent_skill_id'])->value('id');

        return $passiveSkillData;
    }
}
