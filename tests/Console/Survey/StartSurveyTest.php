<?php

namespace Tests\Console\Survey;

use App\Game\Events\Values\EventType;
use App\Game\Survey\Events\ShowSurvey;
use Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateScheduledEvent;
use Tests\Traits\CreateSubmittedSurvey;
use Tests\Traits\CreateSurvey;
use Tests\Traits\CreateUserLoginDuration;

class StartSurveyTest extends TestCase {
    use CreateEvent, RefreshDatabase, CreateScheduledEvent, CreateUserLoginDuration, CreateSubmittedSurvey, CreateSurvey;

    private ?CharacterFactory $characterFactory;


    public function setUp(): void {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory())->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->characterFactory = null;
    }

    public function testCannotStartSurveyWhenNoScheduledEventIsRunning() {

        Event::fake();

        Artisan::call('start:survey');

        Event::assertNotDispatched(ShowSurvey::class);
    }

    public function testCannotStartSurveyWhenFlagIsAlreadyFlippedToShowTheSurvey() {
        Event::fake();

        $character = $this->characterFactory->getCharacter();

        $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'currently_running' => true,
        ]);

        $character->user()->update(['is_showing_survey' => true]);

        Artisan::call('start:survey');

        Event::assertNotDispatched(ShowSurvey::class);
    }

    public function testCannotStartSurveyWhenThereIsNoLoggedInDurationRecord() {
        Event::fake();


        $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'currently_running' => true,
        ]);

        Artisan::call('start:survey');

        Event::assertNotDispatched(ShowSurvey::class);
    }

    public function testCannotStartSurveyWhenUserLoginDurationIsBelowOneHour() {
        Event::fake();


        $character = $this->characterFactory->getCharacter();


        $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'currently_running' => true,
        ]);

        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now(),
            'logged_out_at' => now(),
            'last_activity' => now(),
            'duration_in_seconds' => 0,
            'last_heart_beat' => now(),
        ]);

        Artisan::call('start:survey');

        Event::assertNotDispatched(ShowSurvey::class);
    }

    public function testCannotStartSurveyWhenYouSubmittedASurvey() {
        Event::fake();


        $character = $this->characterFactory->getCharacter();


        $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'currently_running' => true,
        ]);

        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now(),
            'logged_out_at' => now()->addHours(3),
            'last_activity' => now()->addHours(3),
            'duration_in_seconds' => now()->addHours(3)->diffInSeconds(now()),
            'last_heart_beat' => now()->addHours(3),
        ]);

        $this->createSubmittedSurvey([
            'character_id' => $character->id,
            'survey_id' => 1000,
        ]);

        Artisan::call('start:survey');

        Event::assertNotDispatched(ShowSurvey::class);
    }

    public function testCanStartSurvey() {

        $character = $this->characterFactory->getCharacter();

        $this->createSurvey();

        $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'currently_running' => true,
        ]);

        $this->createUserLoginDuration([
            'user_id' => $character->user_id,
            'logged_in_at' => now(),
            'logged_out_at' => now()->addHours(3),
            'last_activity' => now()->addHours(3),
            'duration_in_seconds' => now()->addHours(3)->diffInSeconds(now()),
            'last_heart_beat' => now()->addHours(3),
        ]);

        Artisan::call('start:survey');

        $character = $character->refresh();

        $this->assertTrue($character->user->is_showing_survey);
    }
}
