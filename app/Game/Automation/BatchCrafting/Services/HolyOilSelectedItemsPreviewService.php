<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use Illuminate\Support\Collection;

class HolyOilSelectedItemsPreviewService
{
    public function __construct(private readonly HolyItemService $holyItemService) {}

    /**
     * Build the Holy Oils Selected Items preview payload for the validated Batch Crafting request.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array The lean Holy Oils Selected Items preview payload.
     */
    public function build(Character $character, array $validated): array
    {
        $progress = $validated['progress'];
        $targets = $this->resolveTargets($character, $progress['target_slot_ids']);
        $oilSlots = $this->resolveOilSlots($character, $progress['oil_slot_ids']);
        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, $targets, $oilSlots);
        $goldDustAvailable = $character->gold_dust;

        return [
            'target_count' => $targets->count(),
            'oil_units_available' => $oilSlots->sum('amount'),
            'planned_application_count' => $plan['total_applications'],
            'total_gold_dust_cost' => $plan['total_gold_dust_cost'],
            'gold_dust_available' => $goldDustAvailable,
            'targets' => $plan['targets'],
            'disposition' => $validated['disposition'],
            'listing_price' => $progress['listing_price'] ?? null,
            'blockers' => $this->buildBlockers($targets, $oilSlots, $plan, $goldDustAvailable),
        ];
    }

    /**
     * Resolve the requested target Inventory slots the character actually owns.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array<int, int>  $targetSlotIds  The requested target Inventory slot ids.
     * @return Collection<int, InventorySlot> The resolved target slots.
     */
    private function resolveTargets(Character $character, array $targetSlotIds): Collection
    {
        $inventory = $character->inventory;

        if (is_null($inventory)) {
            return collect();
        }

        return InventorySlot::with('item')
            ->where('inventory_id', $inventory->id)
            ->whereIn('id', $targetSlotIds)
            ->get();
    }

    /**
     * Resolve the requested Holy Oil Alchemy Bag slots the character actually owns.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array<int, int>  $oilSlotIds  The requested Holy Oil Alchemy Bag slot ids.
     * @return Collection<int, AlchemyBagSlot> The resolved oil slots.
     */
    private function resolveOilSlots(Character $character, array $oilSlotIds): Collection
    {
        if (is_null($character->alchemyBag)) {
            return collect();
        }

        return AlchemyBagSlot::with('item')
            ->where('alchemy_bag_id', $character->alchemyBag->id)
            ->where('character_id', $character->id)
            ->whereIn('id', $oilSlotIds)
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->where('can_use_on_other_items', true)->whereNotNull('holy_level');
            })
            ->get();
    }

    /**
     * Build the blocking messages for the Holy Oils Selected Items preview.
     *
     * @param  Collection  $targets  The resolved target slots.
     * @param  Collection  $oilSlots  The resolved oil slots.
     * @param  array  $plan  The simulated application plan.
     * @param  int  $goldDustAvailable  The character's available Gold Dust.
     * @return array<int, string> The blocking messages, empty when nothing blocks the request.
     */
    private function buildBlockers(Collection $targets, Collection $oilSlots, array $plan, int $goldDustAvailable): array
    {
        $blockers = [];

        if ($targets->isEmpty()) {
            $blockers[] = 'None of the selected target items could be found.';
        }

        if ($oilSlots->isEmpty()) {
            $blockers[] = 'None of the selected Holy Oils could be found.';
        }

        if ($plan['total_gold_dust_cost'] > $goldDustAvailable) {
            $blockers[] = 'You do not have enough Gold Dust to apply the planned Holy Oils.';
        }

        return $blockers;
    }
}
