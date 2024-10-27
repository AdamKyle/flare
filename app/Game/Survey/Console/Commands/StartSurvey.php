<?php

namespace App\Game\Survey\Console\Commands;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\Announcement;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\SubmittedSurvey;
use App\Flare\Models\Survey;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Services\EventSchedulerService;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Values\FactionLevel;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Events\Values\EventType;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\TraverseService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Services\BuildQuestCacheService;
use App\Game\Raids\Events\CorruptLocations;
use App\Game\Survey\Events\ShowSurvey;
use Exception;
use Illuminate\Console\Command;

class StartSurvey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start:survey {overrideCharacterId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts the survey for those eligible.';

    public function handle(): void
    {

        $scheduledEvent = ScheduledEvent::where('event_type', EventType::FEEDBACK_EVENT)->where('currently_running', true)->first();

        if (is_null($scheduledEvent)) {
            return;
        }

        $overrideCharacterId = $this->argument('overrideCharacterId');

        $overrideCharacter = Character::find($overrideCharacterId);

        if (!is_null($overrideCharacter)) {
            $overrideCharacter->user()->update([
                'is_showing_survey' => true,
            ]);

            $character = $overrideCharacter->refresh();

            $surveyToComplete = Survey::latest()->first();

            event(new ShowSurvey($character->user, $surveyToComplete->id));

            return;
        }

        Character::chunk(250, function ($characters) {
            foreach ($characters as $character) {

                if ($character->user->is_showing_survey) {
                    continue;
                }

                $userLoginDuration = UserLoginDuration::where('user_id', $character->user->id)->latest()->first();

                if (is_null($userLoginDuration)) {
                    continue;
                }

                $totalLoginDuration      = (int) UserLoginDuration::where('user_id', $character->user->id)->sum('duration_in_seconds');
                $hoursSinceLastHeartBeat = $userLoginDuration->last_heart_beat->diffInHours($userLoginDuration->logged_in_at);
                $hoursSinceLastActivity  = $userLoginDuration->last_activity->diffInHours($userLoginDuration->logged_in_at);

                $totalHoursLoggedIn = $totalLoginDuration / 3600;


                if (($hoursSinceLastActivity < 1 && $hoursSinceLastHeartBeat < 1 && $totalHoursLoggedIn < 1)) {
                    continue;
                }

                $submittedSurvey = SubmittedSurvey::where('character_id', $character->id)->first();

                if (!is_null($submittedSurvey)) {
                    continue;
                }

                $character->user()->update([
                    'is_showing_survey' => true,
                ]);

                $character = $character->refresh();

                $surveyToComplete = Survey::latest()->first();

                event(new ShowSurvey($character->user, $surveyToComplete->id));
            }
        });
    }
}
