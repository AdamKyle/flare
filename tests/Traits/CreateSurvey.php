<?php

namespace Tests\Traits;

use App\Flare\Models\Survey;

trait CreateSurvey
{
    public function CreateSurvey(array $options = []): Survey
    {

        return Survey::factory()->create($options);
    }

}
