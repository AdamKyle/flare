<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_survey_stats_returns_successful_response_when_no_snapshot_exists(): void
    {
        $this->visit('/survey-stats');

        $this->assertResponseOk();
        $this->assertViewHas('surveyExists', false);
    }
}
