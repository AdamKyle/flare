<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Item;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use Illuminate\Support\Collection;

class HolyOilPlanSimulator
{
    /**
     * Simulate a deterministic Holy Oil application plan against the given targets and oil slots.
     *
     * Oils are drawn from the given slots in order, exhausting one slot's amount before moving
     * to the next. Preview-only: the actual batch run resolves oil availability live per operation.
     *
     * @param  HolyItemService  $holyItemService  The Holy Oil domain service providing the real cost formula.
     * @param  Collection  $targets  The resolved target slots, each exposing an `item` relation.
     * @param  Collection  $oilSlots  The resolved Holy Oil Alchemy Bag slots, each exposing an `item` relation and `amount`.
     * @return array{total_applications: int, total_gold_dust_cost: int, targets: array<int, array>} The simulated plan.
     */
    public static function simulate(HolyItemService $holyItemService, Collection $targets, Collection $oilSlots): array
    {
        $oilQueue = $oilSlots->map(fn ($slot) => ['item' => $slot->item, 'remaining' => $slot->amount])->values()->all();
        $totalGoldDustCost = 0;
        $totalApplications = 0;
        $targetPlans = [];

        foreach ($targets as $target) {
            $item = $target->item;
            $eligible = $holyItemService->isEligibleHolyOilTarget($item);
            $currentStacks = $item->holy_stacks_applied;
            $maxStacks = $item->holy_stacks;
            $capacity = $eligible ? max(0, $maxStacks - $currentStacks) : 0;
            $planned = 0;
            $targetCost = 0;

            while ($planned < $capacity) {
                $oilIndex = self::nextAvailableOilIndex($oilQueue);

                if (is_null($oilIndex)) {
                    break;
                }

                $cost = $holyItemService->getCost($item, $oilQueue[$oilIndex]['item']);
                $targetCost += $cost;
                $totalGoldDustCost += $cost;
                $oilQueue[$oilIndex]['remaining']--;
                $planned++;
                $totalApplications++;
            }

            $targetPlans[] = [
                'target_slot_id' => $target->id,
                'item_id' => $item->id,
                'item_name' => $item->affix_name ?? $item->name,
                'eligible' => $eligible,
                'current_holy_stacks' => $currentStacks,
                'max_holy_stacks' => $maxStacks,
                'planned_applications' => $planned,
                'resulting_holy_stacks' => min($maxStacks, $currentStacks + $planned),
                'gold_dust_cost' => $targetCost,
            ];
        }

        return [
            'total_applications' => $totalApplications,
            'total_gold_dust_cost' => $totalGoldDustCost,
            'targets' => $targetPlans,
        ];
    }

    /**
     * Resolve the index of the next oil queue entry with a remaining unit available.
     *
     * @param  array<int, array{item: Item, remaining: int}>  $oilQueue  The oil queue.
     * @return int|null The resolved index, or null when no oil units remain.
     */
    private static function nextAvailableOilIndex(array $oilQueue): ?int
    {
        foreach ($oilQueue as $index => $entry) {
            if ($entry['remaining'] > 0) {
                return $index;
            }
        }

        return null;
    }
}
