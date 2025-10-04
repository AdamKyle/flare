<?php

namespace Tests\Traits;

use App\Flare\Models\SubmittedSurvey;

trait CreateSubmittedSurvey
{
    public function createSubmittedSurvey(array $options = []): SubmittedSurvey
    {

        return SubmittedSurvey::factory()->create($options);
    }
}
