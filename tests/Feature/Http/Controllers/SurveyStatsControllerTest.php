<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyStatsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testSurveyStatsReturnsSuccessfulResponseWhenNoSnapshotExists(): void
    {
        $this->visit('/survey-stats');

        $this->assertResponseOk();
        $this->assertViewHas('surveyExists', false);
    }
}
