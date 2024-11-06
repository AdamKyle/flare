<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\ScheduledEventConfiguration;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Values\EventType;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Maps\Services\TraverseService;

class FixEventTypesOnEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:event-types-on-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix event type on events';

    /**
     * Execute the console command.
     */
    public function handle(TraverseService $traverseService, ExplorationAutomationService $explorationAutomationService)
    {

        ScheduledEventConfiguration::truncate();

        ScheduledEvent::where('start_date', '>', Carbon::now()->addWeeks(2))->delete();

        $scheduledEvents = ScheduledEvent::where('start_date', '>=', Carbon::now())->orderBy('start_date', 'desc')->get()->groupBy('event_type')->map(fn($events) => $events->first());

        foreach ($scheduledEvents as $event) {
            ScheduledEventConfiguration::create([
                'event_type' => $event->event_type,
                'start_date' => $event->start_date,
                'generate_every' => 'weekly',
                'last_time_generated' => now()->subWeeks(2),
            ]);
        }

        GameMap::where('only_during_event_type', 8)->update(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();
        $surfaceMap = GameMap::where('name', MapNameValue::SURFACE)->first();

        Character::select('characters.*')
            ->join('maps', 'maps.character_id', '=', 'characters.id')
            ->where('maps.game_map_id', $gameMap->id)
            ->chunk(100, function ($characters) use (
                $traverseService,
                $surfaceMap,
                $explorationAutomationService,
            ) {
                foreach ($characters as $character) {
                    $explorationAutomationService->stopExploration($character);

                    $traverseService->travel($surfaceMap->id, $character);
                }
            });
    }
}
