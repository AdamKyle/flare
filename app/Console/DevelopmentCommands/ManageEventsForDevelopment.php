<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\DevelopmentEventManager;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class ManageEventsForDevelopment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manage:events-for-development';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively start, view, and cancel running development events and raids.';

    const START = 'Start event(s)';

    const VIEW_CANCEL = 'View / cancel currently running events';

    const EXIT = 'Exit';

    const CANCEL_ALL = 'Cancel all currently running events';

    const BACK = 'Back';

    public function handle(DevelopmentEventManager $developmentEventManager): int
    {
        if (! in_array(config('app.env'), ['local', 'testing'], true)) {
            $this->error('This command can only be run in the local or testing environment.');

            return Command::FAILURE;
        }

        while (true) {
            $choice = $this->choice('What would you like to do?', [self::START, self::VIEW_CANCEL, self::EXIT]);

            if ($choice === self::EXIT) {
                return Command::SUCCESS;
            }

            if ($choice === self::START) {
                $this->handleStart($developmentEventManager);

                continue;
            }

            $this->handleViewAndCancel($developmentEventManager);
        }
    }

    private function handleStart(DevelopmentEventManager $developmentEventManager): void
    {
        if (! $this->queueSupportsDelayedDispatch()) {
            $this->error('The configured default queue connection does not support the required one-minute delayed start.');

            return;
        }

        $selectedLabels = $this->choice(
            'Which event(s) would you like to start?',
            $developmentEventManager->startOptions(),
            null,
            null,
            true
        );

        $raidBearingSelections = array_values(array_intersect($selectedLabels, $developmentEventManager->raidBearingOptions()));

        if (! empty($raidBearingSelections) && empty($developmentEventManager->raidNames())) {
            $this->error('No raids exist to select from. Aborting this start operation.');

            return;
        }

        $raidNameBySelection = [];

        foreach ($raidBearingSelections as $label) {
            if ($label === DevelopmentEventManager::INDEPENDENT_RAID) {
                $raidName = $this->handleIndependentRaidSelection($developmentEventManager);

                if (is_null($raidName)) {
                    $selectedLabels = array_values(array_diff($selectedLabels, [DevelopmentEventManager::INDEPENDENT_RAID]));

                    continue;
                }

                $raidNameBySelection[$label] = $raidName;

                continue;
            }

            $raidNameBySelection[$label] = $this->choice(
                'Which raid would you like to use for: ' . $label . '?',
                $developmentEventManager->raidNames()
            );
        }

        try {
            $resolvedRequest = $developmentEventManager->validateStartRequest($selectedLabels, $raidNameBySelection);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return;
        }

        if (empty($resolvedRequest)) {
            return;
        }

        $confirmedConflicts = $this->resolveConflicts($developmentEventManager, $resolvedRequest);

        if (is_null($confirmedConflicts)) {
            return;
        }

        try {
            $createdSchedules = $developmentEventManager->startAfterConfirmation($resolvedRequest, $confirmedConflicts);
        } catch (Throwable $throwable) {
            $this->error('Failed to start the requested events: ' . $throwable->getMessage());

            return;
        }

        foreach ($createdSchedules as $schedule) {
            if (is_null($schedule->parent_scheduled_event_id)) {
                $this->info($schedule->getTitleOfEvent() . ' queued, starts in one minute.');
            }
        }
    }

    /**
     * Walks the raid-selection prompt for Independent Raid, including the
     * seasonal-requirement prompt loop. Returns the plain raid name to feed
     * into the normal start pipeline when the raid has no seasonal owner.
     * Returns null when the raid was already fully started here (either
     * attached to its already-running season, or started atomically with
     * its season), so the caller must not process Independent Raid again.
     */
    private function handleIndependentRaidSelection(DevelopmentEventManager $developmentEventManager): ?string
    {
        while (true) {
            $raidName = $this->choice(
                'Which raid would you like to use for: ' . DevelopmentEventManager::INDEPENDENT_RAID . '?',
                $developmentEventManager->raidNames()
            );

            $raid = $developmentEventManager->findRaidByName($raidName);

            $seasonalEventType = $developmentEventManager->requiredSeasonalEventTypeForRaid($raid);

            if (is_null($seasonalEventType)) {
                return $raidName;
            }

            $seasonalLabel = $developmentEventManager->seasonalEventLabel($seasonalEventType);

            $runningSeasonalParent = $developmentEventManager->runningSeasonalParent($seasonalEventType);

            if (! is_null($runningSeasonalParent)) {
                $this->startRaidUnderRunningSeason($developmentEventManager, $raid, $runningSeasonalParent);

                return null;
            }

            $this->line($raidName . ' requires ' . $seasonalLabel . ' to be running.');

            $startSeasonToo = $this->confirm('Would you like to start ' . $seasonalLabel . ' as well?', false);

            if (! $startSeasonToo) {
                $this->line('You must have ' . $seasonalLabel . ' running to start ' . $raidName . '.');

                continue;
            }

            $this->startSeasonWithRaid($developmentEventManager, $seasonalLabel, $raidName);

            return null;
        }
    }

    /**
     * Starts the selected raid as a genuine independent-raid schedule
     * attached under the given already-running seasonal parent.
     */
    private function startRaidUnderRunningSeason(DevelopmentEventManager $developmentEventManager, Raid $raid, ScheduledEvent $runningSeasonalParent): void
    {
        try {
            $resolvedRequest = $developmentEventManager->validateStartRequest(
                [DevelopmentEventManager::INDEPENDENT_RAID],
                [DevelopmentEventManager::INDEPENDENT_RAID => $raid->name]
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return;
        }

        if (empty($resolvedRequest)) {
            return;
        }

        $confirmedConflicts = $this->resolveConflicts($developmentEventManager, $resolvedRequest);

        if (is_null($confirmedConflicts)) {
            return;
        }

        try {
            $developmentEventManager->startAfterConfirmation($resolvedRequest, $confirmedConflicts, $runningSeasonalParent);
        } catch (Throwable $throwable) {
            $this->error('Failed to start the requested events: ' . $throwable->getMessage());

            return;
        }

        $this->info($raid->name . ' queued, starts in one minute, as part of ' . $runningSeasonalParent->getTitleOfEvent() . '.');
    }

    /**
     * Starts the exact requested seasonal event and the already-selected
     * raid as its exact child, atomically, exactly like selecting the
     * seasonal event from the start menu directly.
     */
    private function startSeasonWithRaid(DevelopmentEventManager $developmentEventManager, string $seasonalLabel, string $raidName): void
    {
        try {
            $resolvedRequest = $developmentEventManager->validateStartRequest(
                [$seasonalLabel],
                [$seasonalLabel => $raidName]
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return;
        }

        if (empty($resolvedRequest)) {
            return;
        }

        $confirmedConflicts = $this->resolveConflicts($developmentEventManager, $resolvedRequest);

        if (is_null($confirmedConflicts)) {
            return;
        }

        try {
            $createdSchedules = $developmentEventManager->startAfterConfirmation($resolvedRequest, $confirmedConflicts);
        } catch (Throwable $throwable) {
            $this->error('Failed to start the requested events: ' . $throwable->getMessage());

            return;
        }

        foreach ($createdSchedules as $schedule) {
            if (is_null($schedule->parent_scheduled_event_id)) {
                $this->info($schedule->getTitleOfEvent() . ' queued, starts in one minute.');
            }
        }
    }

    /**
     * Walks the existing raid-map conflict confirmation flow. Returns the
     * confirmed conflicts to cancel, or null if the user declined and the
     * caller must abandon the current start operation with no changes made.
     */
    private function resolveConflicts(DevelopmentEventManager $developmentEventManager, array $resolvedRequest): ?array
    {
        $conflicts = $developmentEventManager->findActiveConflicts($resolvedRequest);
        $confirmedConflicts = [];

        foreach ($conflicts as $conflict) {
            $existingRaid = $conflict['existing_schedule']->raid;

            $this->line('Raid ' . $existingRaid->name . ' is active on the same map(s) as ' . $conflict['requested_raid']->name . ': ' . $conflict['shared_map_names'] . '.');

            $confirmed = $this->confirm('Cancel the current raid and continue?', false);

            if (! $confirmed) {
                $this->info('No changes made.');

                return null;
            }

            $confirmedConflicts[] = $conflict;
        }

        return $confirmedConflicts;
    }

    /**
     * Validates the configured default queue connection can satisfy the
     * required one-minute delayed start: queue.default must name an
     * existing array-shaped connection with a non-empty driver that is
     * neither sync nor null.
     */
    private function queueSupportsDelayedDispatch(): bool
    {
        $connection = config('queue.default');

        if (! is_string($connection) || $connection === '') {
            return false;
        }

        $connections = config('queue.connections');

        if (! is_array($connections) || ! array_key_exists($connection, $connections)) {
            return false;
        }

        $connectionConfig = $connections[$connection];

        if (! is_array($connectionConfig) || ! array_key_exists('driver', $connectionConfig)) {
            return false;
        }

        $driver = $connectionConfig['driver'];

        if (! is_string($driver) || $driver === '') {
            return false;
        }

        return ! in_array($driver, ['sync', 'null'], true);
    }

    private function handleViewAndCancel(DevelopmentEventManager $developmentEventManager): void
    {
        $rows = $developmentEventManager->runningScheduleRows();

        if (empty($rows)) {
            $this->line('No events or raids are running right now.');

            return;
        }

        $this->table(
            ['ID', 'Title', 'Status', 'Start', 'End', 'Parent'],
            array_map(function (ScheduledEvent $scheduledEvent) {
                return [
                    $scheduledEvent->id,
                    (is_null($scheduledEvent->parent_scheduled_event_id) ? '' : '-> ') . $scheduledEvent->getTitleOfEvent(),
                    $scheduledEvent->status,
                    $scheduledEvent->start_date->toDateTimeString(),
                    $scheduledEvent->end_date->toDateTimeString(),
                    is_null($scheduledEvent->parent_scheduled_event_id) ? '-' : $scheduledEvent->parent->getTitleOfEvent(),
                ];
            }, $rows)
        );

        $rowLabels = [];

        foreach ($rows as $scheduledEvent) {
            $rowLabels[$this->labelFor($scheduledEvent)] = $scheduledEvent;
        }

        $choices = array_merge([self::CANCEL_ALL], array_keys($rowLabels), [self::BACK]);

        $selected = $this->choice('Select item(s) to cancel, or back:', $choices, null, null, true);

        if (in_array(self::BACK, $selected, true) && count($selected) === 1) {
            return;
        }

        if (in_array(self::CANCEL_ALL, $selected, true)) {
            try {
                $developmentEventManager->cancelAllRunning();
            } catch (Throwable $throwable) {
                $this->error('Failed to cancel all events: ' . $throwable->getMessage());

                return;
            }

            $this->info('All currently running events and raids have been cancelled.');

            return;
        }

        $selectedSchedules = [];

        foreach ($selected as $label) {
            if (! isset($rowLabels[$label])) {
                continue;
            }

            $scheduledEvent = $rowLabels[$label];

            if ($scheduledEvent->children()->exists() && ! $this->confirmSeasonalParentCancellation($scheduledEvent)) {
                continue;
            }

            $selectedSchedules[] = $scheduledEvent;
        }

        try {
            $developmentEventManager->cancelSpecific($selectedSchedules);
        } catch (Throwable $throwable) {
            $this->error('Failed to cancel selected events: ' . $throwable->getMessage());

            return;
        }

        $this->info('Selected events have been cancelled.');
    }

    private function confirmSeasonalParentCancellation(ScheduledEvent $scheduledEvent): bool
    {
        $now = now();

        $runningChildren = $scheduledEvent->children()->runningNow($now)->get();

        if ($runningChildren->isEmpty()) {
            return true;
        }

        $childTitles = $runningChildren->map(fn (ScheduledEvent $child) => $child->getTitleOfEvent())->implode(', ');

        $this->line($scheduledEvent->getTitleOfEvent() . ' has currently running child raid(s) which will be cancelled first: ' . $childTitles);

        return $this->confirm('Cancel ' . $scheduledEvent->getTitleOfEvent() . ' and its currently running child raids?', false);
    }

    private function labelFor(ScheduledEvent $scheduledEvent): string
    {
        return '#' . $scheduledEvent->id . ' ' . $scheduledEvent->getTitleOfEvent() . ' [' . $scheduledEvent->status . ']';
    }
}
