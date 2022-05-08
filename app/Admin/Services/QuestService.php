<?php

namespace App\Admin\Services;

use App\Flare\Models\Quest;

class QuestService {

    public function createOrUpdateQuest(array $params): Quest {
        return Quest::updateOrCreate(['id' => $params['id']], $this->cleanFormParams($params));
    }

    protected function cleanFormParams(array $params): array {
        if (!filter_var($params['unlocks_skill'], FILTER_VALIDATE_BOOLEAN)) {
            $params['unlocks_skill_type'] = null;
            $params['unlocks_skill']      = false;
        }

        if (!filter_var($params['is_parent'], FILTER_VALIDATE_BOOLEAN)) {
            $params['is_parent']          = false;
        }

        if (filter_var($params['is_parent'], FILTER_VALIDATE_BOOLEAN)) {
            $params['is_parent']          = true;
            $params['parent_quest_id']    = false;
        }

        return $params;
    }
}
