<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Raids\Services\RaidMapConflictService;
use App\Game\Raids\Values\RaidType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DevelopmentEventManager
{
    const WEEKLY_CELESTIALS = 'Weekly Celestials';

    const WEEKLY_CURRENCY_DROPS = 'Weekly Currency Drops';

    const WEEKLY_FACTION_LOYALTY = 'Weekly Faction Loyalty Event';

    const INDEPENDENT_RAID = 'Independent Raid';

    const WINTER_EVENT = 'The Winter Event';

    const DELUSIONAL_EVENT = 'Delusional Memories Event';

    const CACHE_LOCK_KEY = 'development-event-manager-lock';

    public function __construct(
        private readonly ScheduledEventDispatchService $scheduledEventDispatchService,
        private readonly EventLifecycleService $eventLifecycleService,
        private readonly RaidMapConflictService $raidMapConflictService,
    ) {}

    /**
     * The exact supported start choices, in menu order.
     *
     * @return string[]
     */
    public function startOptions(): array
    {
        return [
            self::WEEKLY_CELESTIALS,
            self::WEEKLY_CURRENCY_DROPS,
            self::WEEKLY_FACTION_LOYALTY,
            self::INDEPENDENT_RAID,
            self::WINTER_EVENT,
            self::DELUSIONAL_EVENT,
        ];
    }

    /**
     * Which start options require an independent raid prompt.
     *
     * @return string[]
     */
    public function raidBearingOptions(): array
    {
        return [self::INDEPENDENT_RAID, self::WINTER_EVENT, self::DELUSIONAL_EVENT];
    }

    public function raidNames(): array
    {
        return Raid::pluck('name')->toArray();
    }

    /**
     * @throws RuntimeException
     */
    public function findRaidByName(string $raidName): Raid
    {
        $raid = Raid::where('name', $raidName)->first();

        if (is_null($raid)) {
            throw new RuntimeException('Selected raid could not be resolved: ' . $raidName . '.');
        }

        return $raid;
    }

    /**
     * Resolves the raid's seasonal owner using the raid's existing
     * `raid_type` configuration: Ice Queen/Frozen King belong to the Winter
     * Event, Jester of Time/Corrupted Bishop belong to the Delusional
     * Memories Event. Returns null when the raid has no seasonal owner.
     */
    public function requiredSeasonalEventTypeForRaid(Raid $raid): ?int
    {
        if (is_null($raid->raid_type)) {
            return null;
        }

        $raidType = new RaidType($raid->raid_type);

        if ($raidType->isIceQueenRaid() || $raidType->isFrozenKing()) {
            return EventType::WINTER_EVENT;
        }

        if ($raidType->isJesterOfTime() || $raidType->isCorruptedBishop()) {
            return EventType::DELUSIONAL_MEMORIES_EVENT;
        }

        return null;
    }

    /**
     * The display label already used for this seasonal event type's start
     * option, reused so seasonal prompts stay consistent with the menu.
     */
    public function seasonalEventLabel(int $seasonalEventType): string
    {
        return match ($seasonalEventType) {
            EventType::WINTER_EVENT => self::WINTER_EVENT,
            EventType::DELUSIONAL_MEMORIES_EVENT => self::DELUSIONAL_EVENT,
        };
    }

    /**
     * The exact top-level seasonal schedule of this type that is genuinely
     * running right now, if any.
     */
    public function runningSeasonalParent(int $seasonalEventType): ?ScheduledEvent
    {
        return ScheduledEvent::whereNull('parent_scheduled_event_id')
            ->where('event_type', $seasonalEventType)
            ->runningNow(now())
            ->first();
    }

    private function eventTypeForOption(string $option): int
    {
        return match ($option) {
            self::WEEKLY_CELESTIALS => EventType::WEEKLY_CELESTIALS,
            self::WEEKLY_CURRENCY_DROPS => EventType::WEEKLY_CURRENCY_DROPS,
            self::WEEKLY_FACTION_LOYALTY => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
            self::INDEPENDENT_RAID, self::WINTER_EVENT, self::DELUSIONAL_EVENT => $option === self::WINTER_EVENT
                ? EventType::WINTER_EVENT
                : ($option === self::DELUSIONAL_EVENT ? EventType::DELUSIONAL_MEMORIES_EVENT : EventType::RAID_EVENT),
        };
    }

    /**
     * Validates every selected option before any write happens.
     *
     * $raidNameBySelection maps a raid-bearing option label to the raid name
     * chosen for it, e.g. ['Independent Raid' => 'The Frozen King'].
     *
     * Returns a resolved request array:
     * ['label' => string, 'event_type' => int, 'raid' => ?Raid][]
     *
     * @throws RuntimeException
     */
    public function validateStartRequest(array $selectedLabels, array $raidNameBySelection): array
    {
        $resolved = [];

        foreach ($selectedLabels as $label) {
            $raid = null;

            if (in_array($label, $this->raidBearingOptions(), true)) {
                $raidName = $raidNameBySelection[$label] ?? null;

                if (is_null($raidName)) {
                    throw new RuntimeException('No raid was selected for: ' . $label . '.');
                }

                $raid = Raid::where('name', $raidName)->first();

                if (is_null($raid)) {
                    throw new RuntimeException('Selected raid could not be resolved: ' . $raidName . '.');
                }
            }

            $resolved[] = [
                'label' => $label,
                'event_type' => $this->eventTypeForOption($label),
                'raid' => $raid,
            ];
        }

        $this->guardAgainstDuplicateActiveNonRaidTypes($resolved);
        $this->guardAgainstDuplicateActiveRaids($resolved);
        $this->guardAgainstRequestedMapConflicts($resolved);

        return $resolved;
    }

    private function guardAgainstDuplicateActiveNonRaidTypes(array $resolved): void
    {
        foreach ($resolved as $entry) {
            if ($entry['label'] === self::INDEPENDENT_RAID) {
                continue;
            }

            $exists = ScheduledEvent::where('event_type', $entry['event_type'])
                ->whereIn('status', ScheduledEventStatus::activeStatuses())
                ->exists();

            if ($exists) {
                throw new RuntimeException($entry['label'] . ' is already active.');
            }
        }
    }

    private function guardAgainstDuplicateActiveRaids(array $resolved): void
    {
        foreach ($resolved as $entry) {
            if (is_null($entry['raid'])) {
                continue;
            }

            $exists = ScheduledEvent::where('raid_id', $entry['raid']->id)
                ->whereIn('status', ScheduledEventStatus::activeStatuses())
                ->exists();

            if ($exists) {
                throw new RuntimeException('Raid: ' . $entry['raid']->name . ' is already active.');
            }
        }
    }

    private function guardAgainstRequestedMapConflicts(array $resolved): void
    {
        $raids = array_values(array_filter(array_map(fn (array $entry) => $entry['raid'], $resolved)));

        $conflicts = $this->raidMapConflictService->requestedSetConflicts($raids);

        if (empty($conflicts)) {
            return;
        }

        $pair = array_values($conflicts)[0];
        $raidA = $raids[$pair[0]];
        $raidB = $raids[$pair[1]];

        throw new RuntimeException(
            'Requested raids ' . $raidA->name . ' and ' . $raidB->name . ' use the same map(s): '
            . $this->raidMapConflictService->sharedMapNames($raidA, $raidB) . '.'
        );
    }

    /**
     * Every active scheduled event whose map(s) intersect the requested
     * raids, paired with the raid entry that triggered the conflict.
     *
     * @return array{requested_raid: Raid, existing_schedule: ScheduledEvent, shared_map_names: string}[]
     */
    public function findActiveConflicts(array $resolvedRequest): array
    {
        $conflicts = [];

        foreach ($resolvedRequest as $entry) {
            if (is_null($entry['raid'])) {
                continue;
            }

            $existingSchedule = $this->raidMapConflictService->findActiveConflict($entry['raid']);

            if (! is_null($existingSchedule)) {
                $conflicts[] = [
                    'requested_raid' => $entry['raid'],
                    'existing_schedule' => $existingSchedule,
                    'shared_map_names' => $this->raidMapConflictService->sharedMapNames($entry['raid'], $existingSchedule->raid),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Re-validates everything under the manager lock, cancels every
     * confirmed-and-still-conflicting schedule, then creates and dispatches
     * the requested schedules. Creation happens in a single transaction;
     * dispatch happens only after that transaction commits. If creation or
     * dispatch fails, any schedules newly created by this call are removed
     * and the failure is rethrown. Cancelled conflicting schedules are not
     * reconstructed; teardown is not transactionally reversible.
     *
     * $existingSeasonalParent attaches the (always single, independent-raid)
     * requested schedule under an already-running seasonal schedule instead
     * of creating it as a root schedule. Because that parent is not created
     * by this call, and its own start job will not start this raid, the
     * attached schedule is still dispatched directly like a root schedule.
     *
     * @return ScheduledEvent[] parent-before-child ordered created schedules
     */
    public function startAfterConfirmation(array $resolvedRequest, array $confirmedConflicts, ?ScheduledEvent $existingSeasonalParent = null): array
    {
        $lock = Cache::lock(self::CACHE_LOCK_KEY, 30);

        return $lock->block(10, function () use ($resolvedRequest, $confirmedConflicts, $existingSeasonalParent) {
            $reloadedRequest = $this->reloadResolvedRequest($resolvedRequest);
            $confirmedScheduleIds = $this->confirmedConflictScheduleIds($confirmedConflicts);

            $this->guardAgainstDuplicateActiveNonRaidTypes($reloadedRequest);
            $this->guardAgainstDuplicateActiveRaids($reloadedRequest);
            $this->guardAgainstRequestedMapConflicts($reloadedRequest);

            $finalConflictScheduleIds = $this->reconcileExistingConflicts($reloadedRequest, $confirmedScheduleIds);

            foreach ($finalConflictScheduleIds as $scheduleId) {
                $schedule = ScheduledEvent::findOrFail($scheduleId);

                $this->eventLifecycleService->cancel($schedule);

                if (! $schedule->fresh()->status()->isTerminal()) {
                    throw new RuntimeException('Conflicting scheduled event id: ' . $scheduleId . ' did not reach a terminal status.');
                }
            }

            $existingSeasonalParentId = null;

            if (! is_null($existingSeasonalParent)) {
                if (! ScheduledEvent::whereKey($existingSeasonalParent->id)->runningNow(now())->exists()) {
                    throw new RuntimeException($existingSeasonalParent->getTitleOfEvent() . ' is no longer running. No changes made.');
                }

                $existingSeasonalParentId = $existingSeasonalParent->id;
            }

            $operationTime = now();
            $startDate = $operationTime->copy()->addMinute();
            $endDate = $startDate->copy()->addMinutes(5);

            $createdScheduleIds = [];

            try {
                $created = DB::transaction(function () use ($reloadedRequest, $startDate, $endDate, $existingSeasonalParentId, &$createdScheduleIds) {
                    $schedules = [];

                    foreach ($reloadedRequest as $entry) {
                        $parent = ScheduledEvent::create([
                            'event_type' => $entry['event_type'],
                            'raid_id' => $entry['label'] === self::INDEPENDENT_RAID ? $entry['raid']->id : null,
                            'parent_scheduled_event_id' => $existingSeasonalParentId,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'description' => $this->descriptionFor($entry),
                            'status' => ScheduledEventStatus::SCHEDULED,
                        ]);

                        $createdScheduleIds[] = $parent->id;
                        $schedules[] = $parent;

                        if (in_array($entry['label'], [self::WINTER_EVENT, self::DELUSIONAL_EVENT], true)) {
                            $child = ScheduledEvent::create([
                                'event_type' => EventType::RAID_EVENT,
                                'raid_id' => $entry['raid']->id,
                                'parent_scheduled_event_id' => $parent->id,
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'description' => $entry['raid']->scheduled_event_description ?? $entry['raid']->name,
                                'status' => ScheduledEventStatus::SCHEDULED,
                            ]);

                            $createdScheduleIds[] = $child->id;
                            $schedules[] = $child;
                        }
                    }

                    return $schedules;
                });

                foreach ($created as $schedule) {
                    if (is_null($schedule->parent_scheduled_event_id) || $schedule->parent_scheduled_event_id === $existingSeasonalParentId) {
                        $this->scheduledEventDispatchService->dispatch($schedule, $schedule->start_date);
                    }
                }

                return $created;
            } catch (Throwable $throwable) {
                ScheduledEvent::whereIn('id', $createdScheduleIds)->delete();

                throw $throwable;
            }
        });
    }

    /**
     * Reloads every requested raid by id, so start validation never trusts a
     * stale raid model resolved before the user was asked to confirm.
     */
    private function reloadResolvedRequest(array $resolvedRequest): array
    {
        return array_map(function (array $entry) {
            if (is_null($entry['raid'])) {
                return $entry;
            }

            $raid = Raid::find($entry['raid']->id);

            if (is_null($raid)) {
                throw new RuntimeException('Selected raid no longer exists for: ' . $entry['label'] . '.');
            }

            return array_merge($entry, ['raid' => $raid]);
        }, $resolvedRequest);
    }

    /**
     * @return int[]
     */
    private function confirmedConflictScheduleIds(array $confirmedConflicts): array
    {
        return array_values(array_unique(array_map(
            fn (array $conflict) => $conflict['existing_schedule']->id,
            $confirmedConflicts
        )));
    }

    /**
     * Recomputes every requested-vs-existing raid map conflict against
     * current database state. Aborts if a conflict now exists that was never
     * confirmed, or if a confirmed conflict no longer exists / is no longer
     * active, since either means the confirmed set no longer matches reality.
     *
     * @param  int[]  $confirmedScheduleIds
     * @return int[] deduplicated schedule ids to cancel before creating replacements
     */
    private function reconcileExistingConflicts(array $reloadedRequest, array $confirmedScheduleIds): array
    {
        $currentConflictIds = [];

        foreach ($reloadedRequest as $entry) {
            if (is_null($entry['raid'])) {
                continue;
            }

            $existingSchedule = $this->raidMapConflictService->findActiveConflict($entry['raid']);

            if (is_null($existingSchedule)) {
                continue;
            }

            if (! in_array($existingSchedule->id, $confirmedScheduleIds, true)) {
                throw new RuntimeException(
                    'A new conflicting raid appeared before the start could be confirmed: ' . $existingSchedule->raid->name . '. No changes made.'
                );
            }

            $currentConflictIds[] = $existingSchedule->id;
        }

        $currentConflictIds = array_values(array_unique($currentConflictIds));

        if (count($currentConflictIds) !== count($confirmedScheduleIds)) {
            throw new RuntimeException('A confirmed conflicting raid no longer conflicts or is no longer active. No changes made.');
        }

        return $currentConflictIds;
    }

    private function descriptionFor(array $entry): string
    {
        if (! is_null($entry['raid'])) {
            return $entry['raid']->scheduled_event_description ?? $entry['raid']->name;
        }

        return $entry['label'];
    }

    /**
     * Every schedule genuinely running right now, in parent-before-child
     * order for display. A seasonal parent only appears when it is itself
     * running now; its children are limited to the exact child raids also
     * running now.
     *
     * @return ScheduledEvent[]
     */
    public function runningScheduleRows(): array
    {
        $now = now();

        $topLevel = ScheduledEvent::whereNull('parent_scheduled_event_id')
            ->runningNow($now)
            ->with([
                'raid',
                'runtimeEvent',
                'children' => fn ($query) => $query->runningNow($now)->orderBy('id')->with(['raid', 'runtimeEvent']),
            ])
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($topLevel as $parent) {
            $rows[] = $parent;

            foreach ($parent->children as $child) {
                $child->setRelation('parent', $parent);
                $rows[] = $child;
            }
        }

        return $rows;
    }

    /**
     * Cancels the exact schedules requested, reloaded and filtered down to
     * only those still running right now, deduplicating a child that is
     * also covered by its parent being cancelled in the same request.
     */
    public function cancelSpecific(array $scheduledEvents): void
    {
        $now = now();

        $requestedIds = array_values(array_unique(array_map(
            fn (ScheduledEvent $scheduledEvent) => $scheduledEvent->id,
            $scheduledEvents
        )));

        $runningSchedules = ScheduledEvent::whereIn('id', $requestedIds)
            ->runningNow($now)
            ->get();

        $parentIds = $runningSchedules
            ->filter(fn (ScheduledEvent $scheduledEvent) => is_null($scheduledEvent->parent_scheduled_event_id))
            ->pluck('id')
            ->all();

        foreach ($runningSchedules as $scheduledEvent) {
            if (! is_null($scheduledEvent->parent_scheduled_event_id) && in_array($scheduledEvent->parent_scheduled_event_id, $parentIds, true)) {
                continue;
            }

            $this->eventLifecycleService->cancel($scheduledEvent);
        }
    }

    /**
     * Cancels every schedule genuinely running right now, in dependency
     * order: running child raids, then running independent raids, then
     * running seasonal parents, then running weekly events. Processed ids
     * are tracked so a running child is never cancelled twice.
     */
    public function cancelAllRunning(): void
    {
        $now = now();

        $processedIds = [];

        $cancelIfNotProcessed = function (ScheduledEvent $scheduledEvent) use (&$processedIds) {
            if (in_array($scheduledEvent->id, $processedIds, true)) {
                return;
            }

            $this->eventLifecycleService->cancel($scheduledEvent);

            $processedIds[] = $scheduledEvent->id;
        };

        ScheduledEvent::whereNotNull('parent_scheduled_event_id')
            ->runningNow($now)
            ->get()
            ->each($cancelIfNotProcessed);

        ScheduledEvent::whereNull('parent_scheduled_event_id')
            ->whereNotNull('raid_id')
            ->runningNow($now)
            ->get()
            ->each($cancelIfNotProcessed);

        ScheduledEvent::whereNull('parent_scheduled_event_id')
            ->whereIn('event_type', [EventType::WINTER_EVENT, EventType::DELUSIONAL_MEMORIES_EVENT])
            ->runningNow($now)
            ->get()
            ->each($cancelIfNotProcessed);

        ScheduledEvent::whereNull('parent_scheduled_event_id')
            ->whereNull('raid_id')
            ->whereIn('event_type', [EventType::WEEKLY_CELESTIALS, EventType::WEEKLY_CURRENCY_DROPS, EventType::WEEKLY_FACTION_LOYALTY_EVENT])
            ->runningNow($now)
            ->get()
            ->each($cancelIfNotProcessed);
    }
}
