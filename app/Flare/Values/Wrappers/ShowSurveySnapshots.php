<?php

namespace App\Flare\Values\Wrappers;

use App\Flare\Models\SurveySnapshot;

class ShowSurveySnapshots
{
    public static function canShowSurveyMenuOption(): bool
    {

        return SurveySnapshot::exists();
    }
}
