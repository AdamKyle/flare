<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event as ActiveEvent;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\SubmittedSurvey;
use App\Flare\Services\CreateSurveySnapshot;
use App\Game\Events\Services\Concerns\EventEnder;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Survey\Events\ShowSurvey;

class FeedbackEventEnderService implements EventEnder
{
    public function __construct(
        private readonly CreateSurveySnapshot $createSurveySnapshot,
        private readonly AnnouncementCleanupService $announcementCleanup,
    ) {}

    public function supports(EventType $type): bool
    {
        return $type->isFeedbackEvent();
    }

    public function end(EventType $type, ScheduledEvent $scheduled, ActiveEvent $current): void
    {
        event(new GlobalMessageEvent(
            'The Creator thanks all his players for their valuable feedback. At this time the survey has closed! Feedback is being gathered as we speak'
        ));

        $this->createSurveySnapshot->createSnapShop();

        SubmittedSurvey::truncate();

        Character::chunkById(250, function ($characters) {
            foreach ($characters as $character) {
                $character->user()->update(['is_showing_survey' => false]);
                $character = $character->refresh();
                event(new ShowSurvey($character->user));
            }
        });

        event(new GlobalMessageEvent(
            'Survey stats have been generated. The Creator has yet to leave a response. You can see these stats by
        refreshing and clicking the left side bar, there will be a new menu option for the survey stats. Once The Creator has a chance to look
        at them, you will find a button at the bottom called The Creators Response, this will be a detailed post about how the stats impact the
        direction Tlessa goes in, in order for it be the best PBBG out there!'
        ));

        $this->announcementCleanup->deleteByEventId($current->id);
        $current->delete();
    }
}
