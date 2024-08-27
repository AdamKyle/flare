<?php

namespace Tests\Traits;

use App\Flare\Models\SubmittedSurvey;
use App\Flare\Models\Survey;

trait CreateSubmittedSurvey
{
    public function createSubmittedSurvey(array $options = []): SubmittedSurvey
    {

        return SubmittedSurvey::factory()->create($options);
    }

}
