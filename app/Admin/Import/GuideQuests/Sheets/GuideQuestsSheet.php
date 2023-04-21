<?php

namespace App\Admin\Import\GuideQuests\Sheets;

use App\Flare\Models\GuideQuest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class GuideQuestsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {
                $guideQuest = array_combine($rows[0]->toArray(), $row->toArray());

                $guideQuestData = $this->returnCleanAffix($guideQuest);

                if (is_null($guideQuestData)) {
                    continue;
                } else {
                    $foundGuideQuest = GuideQuest::find($guideQuestData['id']);

                    if (!is_null($foundGuideQuest)) {
                        $foundGuideQuest->update($guideQuestData);
                    } else {
                        GuideQuest::create($guideQuestData);
                    }
                }
            }
        }
    }

    protected function returnCleanAffix(array $data) {
        if (!is_null($data['required_skill_level']) && is_null($data['required_skill'])) {
            $data['required_skill_level'] = null;
        }

        if (!is_null($data['required_passive_skill']) && is_null($data['required_skill'])) {
            $data['required_passive_level'] = null;
        }

        if (!is_null($data['required_faction_level']) && is_null($data['required_faction_id'])) {
            $data['required_faction_id'] = null;
        }

        return $data;
    }
}
