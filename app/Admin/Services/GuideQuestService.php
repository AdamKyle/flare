<?php

namespace App\Admin\Services;

class GuideQuestService
{
    public function cleanRequest(array $params): array
    {
        if (! is_null($params['required_skill_level']) && is_null($params['required_skill'])) {
            $params['required_skill_level'] = null;
        }

        if (! is_null($params['required_passive_level']) && is_null($params['required_passive_skill'])) {
            $params['required_passive_level'] = null;
        }

        if (! is_null($params['required_faction_level']) && is_null($params['required_faction_id'])) {
            $params['required_faction_id'] = null;
        }

        return $params;
    }
}
