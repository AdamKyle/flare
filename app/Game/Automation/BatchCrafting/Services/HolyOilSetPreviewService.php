<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use Illuminate\Support\Collection;

class HolyOilSetPreviewService
{
    public function __construct(
        private readonly HolyItemService $holyItemService,
        private readonly CharacterInventoryService $characterInventoryService,
    ) {}

    /**
     * Build the Holy Oils Inventory Set preview payload for the validated Batch Crafting request.
     *
     * The client never sends target item ids for this mode; every eligible target is resolved
     * server-side from the Set's real contents.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array The lean Holy Oils Inventory Set preview payload.
     */
    public function build(Character $character, array $validated): array
    {
        $progress = $validated['progress'];
        $set = $this->characterInventoryService->setCharacter($character)->findOwnedInventorySet($progress['inventory_set_id']);

        if (is_null($set)) {
            return $this->buildUnavailableSetPreview($character, $progress);
        }

        $targets = $set->slots()->with('item')->get();
        $oilSlots = $this->resolveOilSlots($character, $progress['oil_slot_ids']);
        $plan = HolyOilPlanSimulator::simulate($this->holyItemService, $targets, $oilSlots);
        $goldDustAvailable = $character->gold_dust;

        return [
            'set_id' => $set->id,
            'set_name' => $set->name ?? ('Set '.$set->id),
            'target_count' => $targets->count(),
            'oil_units_available' => $oilSlots->sum('amount'),
            'planned_application_count' => $plan['total_applications'],
            'total_gold_dust_cost' => $plan['total_gold_dust_cost'],
            'gold_dust_available' => $goldDustAvailable,
            'targets' => $plan['targets'],
            'disposition' => $validated['disposition'],
            'listing_price' => $progress['listing_price'] ?? null,
            'blockers' => $this->buildBlockers($oilSlots, $plan, $goldDustAvailable),
        ];
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
     * Build the blocking messages for the Holy Oils Inventory Set preview.
     *
     * @param  Collection  $oilSlots  The resolved oil slots.
     * @param  array  $plan  The simulated application plan.
     * @param  int  $goldDustAvailable  The character's available Gold Dust.
     * @return array<int, string> The blocking messages, empty when nothing blocks the request.
     */
    private function buildBlockers(Collection $oilSlots, array $plan, int $goldDustAvailable): array
    {
        $blockers = [];

        if ($oilSlots->isEmpty()) {
            $blockers[] = 'None of the selected Holy Oils could be found.';
        }

        if ($plan['total_gold_dust_cost'] > $goldDustAvailable) {
            $blockers[] = 'You do not have enough Gold Dust to apply the planned Holy Oils.';
        }

        return $blockers;
    }

    /**
     * Build the lean preview payload for a requested Inventory Set that is no longer valid.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Holy Oils Inventory Set progress data.
     * @return array The lean unavailable-set preview payload.
     */
    private function buildUnavailableSetPreview(Character $character, array $progress): array
    {
        return [
            'set_id' => $progress['inventory_set_id'],
            'set_name' => null,
            'target_count' => 0,
            'oil_units_available' => 0,
            'planned_application_count' => 0,
            'total_gold_dust_cost' => 0,
            'gold_dust_available' => $character->gold_dust,
            'targets' => [],
            'disposition' => null,
            'listing_price' => $progress['listing_price'] ?? null,
            'blockers' => ['The selected Inventory Set is no longer available.'],
        ];
    }
}
