<?php

namespace App\Game\Kingdoms\Console\Commands;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Console\Command;

class RepairKingdomData extends Command
{
    protected $signature = 'kingdoms:repair-data {--apply}';

    protected $description = 'Detects and repairs stale or broken kingdom data.';

    private array $terminalCapitalCityStatuses = [
        CapitalCityQueueStatus::FINISHED,
        CapitalCityQueueStatus::REJECTED,
        CapitalCityQueueStatus::CANCELLED,
        CapitalCityQueueStatus::CANCELLATION_REJECTED,
    ];

    private array $resourceFields = [
        'current_stone',
        'current_wood',
        'current_clay',
        'current_iron',
        'current_steel',
        'current_population',
        'treasury',
        'gold_bars',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $counts = [
            'over_level_buildings' => $this->repairOverLevelBuildings($apply),
            'negative_resources' => $this->repairNegativeResources($apply),
            'invalid_manual_building_queues' => $this->repairInvalidManualBuildingQueues($apply),
            'duplicate_manual_building_queues' => $this->repairDuplicateManualBuildingQueues($apply),
            'invalid_manual_unit_queues' => $this->repairInvalidManualUnitQueues($apply),
            'over_max_manual_unit_queues' => $this->repairOverMaxManualUnitQueues($apply),
            'stale_capital_city_building_rows' => $this->repairStaleCapitalCityBuildingRows($apply),
            'stale_capital_city_unit_rows' => $this->repairStaleCapitalCityUnitRows($apply),
            'stuck_building_cancellations' => $this->repairStuckBuildingCancellations($apply),
            'stuck_unit_cancellations' => $this->repairStuckUnitCancellations($apply),
        ];

        $this->info($apply ? 'Apply mode: repaired invalid kingdom data.' : 'Dry-run mode: no data was changed.');

        foreach ($counts as $label => $count) {
            $this->info($label . ': ' . $count);
        }

        $this->info('total_repairs: ' . array_sum($counts));

        return self::SUCCESS;
    }

    private function repairOverLevelBuildings(bool $apply): int
    {
        $count = 0;

        KingdomBuilding::query()->with('gameBuilding')->get()->each(function (KingdomBuilding $building) use ($apply, &$count) {
            if (is_null($building->gameBuilding)) {
                return;
            }

            if ($building->level <= $building->gameBuilding->max_level) {
                return;
            }

            $count++;

            if ($apply) {
                $building->update(['level' => $building->gameBuilding->max_level]);
            }
        });

        return $count;
    }

    private function repairNegativeResources(bool $apply): int
    {
        $count = 0;

        Kingdom::query()->get()->each(function (Kingdom $kingdom) use ($apply, &$count) {
            $updates = [];

            foreach ($this->resourceFields as $field) {
                if (! is_null($kingdom->{$field}) && $kingdom->{$field} < 0) {
                    $updates[$field] = 0;
                }
            }

            if (empty($updates)) {
                return;
            }

            $count++;

            if ($apply) {
                $kingdom->update($updates);
            }
        });

        return $count;
    }

    private function repairInvalidManualBuildingQueues(bool $apply): int
    {
        $count = 0;

        BuildingInQueue::query()->with(['building.gameBuilding', 'kingdom', 'character'])->get()->each(function (BuildingInQueue $queue) use ($apply, &$count) {
            if (! $this->manualBuildingQueueIsInvalid($queue)) {
                return;
            }

            $count++;

            if ($apply) {
                $queue->delete();
            }
        });

        return $count;
    }

    private function repairDuplicateManualBuildingQueues(bool $apply): int
    {
        $count = 0;
        $seen = [];

        BuildingInQueue::query()
            ->where('completed_at', '>', now())
            ->orderBy('id')
            ->get()
            ->each(function (BuildingInQueue $queue) use ($apply, &$count, &$seen) {
                $key = $queue->kingdom_id . ':' . $queue->building_id . ':' . $queue->type;

                if (! isset($seen[$key])) {
                    $seen[$key] = true;

                    return;
                }

                $count++;

                if ($apply) {
                    $queue->delete();
                }
            });

        return $count;
    }

    private function repairInvalidManualUnitQueues(bool $apply): int
    {
        $count = 0;

        UnitInQueue::query()->with(['kingdom', 'character', 'unit'])->get()->each(function (UnitInQueue $queue) use ($apply, &$count) {
            if (! $this->manualUnitQueueIsInvalid($queue)) {
                return;
            }

            $count++;

            if ($apply) {
                $queue->delete();
            }
        });

        return $count;
    }

    private function repairOverMaxManualUnitQueues(bool $apply): int
    {
        $count = 0;
        $trackedAmounts = [];
        $acceptedQueueExists = [];

        UnitInQueue::query()
            ->where('completed_at', '>', now())
            ->with('kingdom')
            ->orderBy('id')
            ->get()
            ->each(function (UnitInQueue $queue) use ($apply, &$count, &$trackedAmounts, &$acceptedQueueExists) {
                if (is_null($queue->kingdom) || $queue->amount <= 0) {
                    return;
                }

                $key = $queue->kingdom_id . ':' . $queue->game_unit_id;

                if (! isset($trackedAmounts[$key])) {
                    $trackedAmounts[$key] = KingdomUnit::query()
                        ->where('kingdom_id', $queue->kingdom_id)
                        ->where('game_unit_id', $queue->game_unit_id)
                        ->sum('amount');
                    $acceptedQueueExists[$key] = false;
                }

                $remaining = KingdomMaxValue::MAX_UNIT - $trackedAmounts[$key];

                if ($remaining <= 0) {
                    $count++;

                    if ($apply) {
                        $queue->delete();
                    }

                    return;
                }

                if ($queue->amount > $remaining) {
                    $count++;

                    if ($apply) {
                        if ($acceptedQueueExists[$key]) {
                            $queue->update(['amount' => $remaining]);
                        } else {
                            $queue->delete();
                        }
                    }

                    $trackedAmounts[$key] = KingdomMaxValue::MAX_UNIT;

                    return;
                }

                $trackedAmounts[$key] += $queue->amount;
                $acceptedQueueExists[$key] = true;
            });

        return $count;
    }

    private function repairStaleCapitalCityBuildingRows(bool $apply): int
    {
        $count = 0;

        CapitalCityBuildingQueue::query()
            ->whereNotIn('status', $this->terminalCapitalCityStatuses)
            ->get()
            ->each(function (CapitalCityBuildingQueue $queue) use ($apply, &$count) {
                $buildingRequestData = collect($queue->building_request_data)->map(function (array $request) use (&$count) {
                    if ($this->capitalCityBuildingRequestIsTerminal($request)) {
                        return $request;
                    }

                    if (! $this->capitalCityBuildingRequestIsInvalid($request)) {
                        return $request;
                    }

                    $request['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                    $count++;

                    return $request;
                })->toArray();

                if ($apply) {
                    $updates = ['building_request_data' => $buildingRequestData];

                    if (! $this->hasOpenCapitalCityRows($buildingRequestData)) {
                        $updates['status'] = CapitalCityQueueStatus::REJECTED;
                    }

                    $queue->update($updates);
                }
            });

        return $count;
    }

    private function repairStaleCapitalCityUnitRows(bool $apply): int
    {
        $count = 0;
        $trackedAmounts = [];

        CapitalCityUnitQueue::query()
            ->whereNotIn('status', $this->terminalCapitalCityStatuses)
            ->orderBy('id')
            ->get()
            ->each(function (CapitalCityUnitQueue $queue) use ($apply, &$count, &$trackedAmounts) {
                $unitRequestData = collect($queue->unit_request_data)->map(function (array $request) use ($queue, &$count, &$trackedAmounts) {
                    if ($this->capitalCityUnitRequestIsTerminal($request)) {
                        return $request;
                    }

                    $gameUnit = GameUnit::query()->where('name', $request['name'] ?? null)->first();

                    if (is_null($gameUnit) || ! isset($request['amount']) || $request['amount'] <= 0) {
                        $request['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                        $count++;

                        return $request;
                    }

                    $key = $queue->kingdom_id . ':' . $gameUnit->id;

                    if (! isset($trackedAmounts[$key])) {
                        $trackedAmounts[$key] = KingdomUnit::query()
                            ->where('kingdom_id', $queue->kingdom_id)
                            ->where('game_unit_id', $gameUnit->id)
                            ->sum('amount');

                        $trackedAmounts[$key] += UnitInQueue::query()
                            ->where('kingdom_id', $queue->kingdom_id)
                            ->where('game_unit_id', $gameUnit->id)
                            ->where('completed_at', '>', now())
                            ->sum('amount');
                    }

                    if ($trackedAmounts[$key] + $request['amount'] > KingdomMaxValue::MAX_UNIT) {
                        $request['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                        $count++;

                        return $request;
                    }

                    $trackedAmounts[$key] += $request['amount'];

                    return $request;
                })->toArray();

                if ($apply) {
                    $updates = ['unit_request_data' => $unitRequestData];

                    if (! $this->hasOpenCapitalCityRows($unitRequestData)) {
                        $updates['status'] = CapitalCityQueueStatus::REJECTED;
                    }

                    $queue->update($updates);
                }
            });

        return $count;
    }

    private function repairStuckBuildingCancellations(bool $apply): int
    {
        $count = 0;

        CapitalCityBuildingCancellation::query()
            ->whereNotIn('status', $this->terminalCapitalCityStatuses)
            ->orWhereNull('status')
            ->get()
            ->each(function (CapitalCityBuildingCancellation $cancellation) use ($apply, &$count) {
                $activeQueueExists = BuildingInQueue::query()
                    ->where('kingdom_id', $cancellation->kingdom_id)
                    ->where('building_id', $cancellation->building_id)
                    ->where('completed_at', '>', now())
                    ->exists();

                if (! is_null($cancellation->capitalCityBuildingQueue) && $activeQueueExists) {
                    return;
                }

                $count++;

                if ($apply) {
                    $cancellation->update(['status' => CapitalCityQueueStatus::CANCELLATION_REJECTED]);
                }
            });

        return $count;
    }

    private function repairStuckUnitCancellations(bool $apply): int
    {
        $count = 0;

        CapitalCityUnitCancellation::query()
            ->whereNotIn('status', $this->terminalCapitalCityStatuses)
            ->orWhereNull('status')
            ->get()
            ->each(function (CapitalCityUnitCancellation $cancellation) use ($apply, &$count) {
                $activeQueueExists = UnitInQueue::query()
                    ->where('kingdom_id', $cancellation->kingdom_id)
                    ->where('game_unit_id', $cancellation->unit_id)
                    ->where('completed_at', '>', now())
                    ->exists();

                if (! is_null($cancellation->capitalCityUnitQueue) && $activeQueueExists) {
                    return;
                }

                $count++;

                if ($apply) {
                    $cancellation->update(['status' => CapitalCityQueueStatus::CANCELLATION_REJECTED]);
                }
            });

        return $count;
    }

    private function manualBuildingQueueIsInvalid(BuildingInQueue $queue): bool
    {
        if (is_null($queue->kingdom) || is_null($queue->character) || is_null($queue->building) || is_null($queue->building->gameBuilding)) {
            return true;
        }

        if (! in_array($queue->type, [BuildingQueueType::UPGRADE, BuildingQueueType::REPAIR], true)) {
            return true;
        }

        if ($queue->type === BuildingQueueType::UPGRADE && $queue->building->level >= $queue->building->gameBuilding->max_level) {
            return true;
        }

        if ($queue->type === BuildingQueueType::UPGRADE && $queue->to_level > $queue->building->gameBuilding->max_level) {
            return true;
        }

        if (! is_null($queue->from_level) && $queue->type === BuildingQueueType::UPGRADE && $queue->from_level !== $queue->building->level) {
            return true;
        }

        if ($queue->type === BuildingQueueType::UPGRADE && $queue->to_level !== $queue->building->level + 1) {
            return true;
        }

        return false;
    }

    private function manualUnitQueueIsInvalid(UnitInQueue $queue): bool
    {
        return is_null($queue->kingdom) || is_null($queue->character) || is_null($queue->unit) || $queue->amount <= 0;
    }

    private function capitalCityBuildingRequestIsTerminal(array $request): bool
    {
        return in_array($request['secondary_status'] ?? null, $this->terminalCapitalCityStatuses, true);
    }

    private function capitalCityUnitRequestIsTerminal(array $request): bool
    {
        return in_array($request['secondary_status'] ?? null, $this->terminalCapitalCityStatuses, true);
    }

    private function capitalCityBuildingRequestIsInvalid(array $request): bool
    {
        $building = KingdomBuilding::query()->with('gameBuilding')->find($request['building_id'] ?? null);

        if (is_null($building) || is_null($building->gameBuilding)) {
            return true;
        }

        if (! isset($request['from_level'], $request['to_level'])) {
            return true;
        }

        if ($request['from_level'] !== $building->level) {
            return true;
        }

        if ($request['to_level'] !== $building->level + 1) {
            return true;
        }

        if ($building->level >= $building->gameBuilding->max_level) {
            return true;
        }

        return $request['to_level'] > $building->gameBuilding->max_level;
    }

    private function hasOpenCapitalCityRows(array $rows): bool
    {
        return collect($rows)->contains(function (array $row) {
            return ! in_array($row['secondary_status'] ?? null, $this->terminalCapitalCityStatuses, true);
        });
    }
}
