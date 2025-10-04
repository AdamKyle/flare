<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Announcement;
use App\Flare\Models\Character;
use App\Flare\Models\Event as ActiveEventModel;
use App\Flare\Models\SubmittedSurvey;
use App\Flare\Models\SurveySnapshot;
use App\Game\Events\Services\FeedbackEventEnderService;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Survey\Events\ShowSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateScheduledEvent;
use Tests\Traits\CreateSubmittedSurvey;
use Tests\Traits\CreateSurvey;

class FeedbackEventEnderServiceTest extends TestCase
{
    use CreateAnnouncement,
        CreateEvent,
        CreateScheduledEvent,
        CreateSubmittedSurvey,
        CreateSurvey,
        RefreshDatabase;

    private ?FeedbackEventEnderService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        EventFacade::fake([
            GlobalMessageEvent::class,
            ShowSurvey::class,
        ]);

        $this->service = $this->app->make(FeedbackEventEnderService::class);
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_supports_returns_true_only_for_feedback_event(): void
    {
        $this->assertTrue($this->service->supports(new EventType(EventType::FEEDBACK_EVENT)));
        $this->assertFalse($this->service->supports(new EventType(EventType::WEEKLY_CELESTIALS)));
    }

    public function test_end_generates_snapshot_truncates_surveys_dispatches_events_cleans_announcements_and_deletes_event(): void
    {
        $survey = $this->createSurvey();

        $charA = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $charB = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        Character::query()->each(fn ($c) => $c->user()->update(['is_showing_survey' => true]));

        $this->createSubmittedSurvey([
            'character_id' => $charA->id,
            'survey_id' => $survey->id,
        ]);

        $this->createSubmittedSurvey([
            'character_id' => $charB->id,
            'survey_id' => $survey->id,
        ]);

        $scheduled = $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'start_date' => now()->subHour(),
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::FEEDBACK_EVENT,
            'started_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->createAnnouncement([
            'event_id' => $current->id,
        ]);

        $this->service->end(new EventType(EventType::FEEDBACK_EVENT), $scheduled, $current);

        EventFacade::assertDispatchedTimes(GlobalMessageEvent::class, 2);
        EventFacade::assertDispatchedTimes(ShowSurvey::class, Character::count());

        $this->assertNotNull(SurveySnapshot::first());
        $this->assertSame(0, SubmittedSurvey::count());
        $this->assertSame(0, ActiveEventModel::where('id', $current->id)->count());
        $this->assertSame(0, Announcement::where('event_id', $current->id)->count());

        Character::query()->each(function ($c) {
            $this->assertFalse($c->user->refresh()->is_showing_survey);
        });
    }
}
