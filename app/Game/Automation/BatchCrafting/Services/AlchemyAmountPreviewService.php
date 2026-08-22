<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Skills\Services\AlchemyService;

class AlchemyAmountPreviewService
{
    public function __construct(private readonly AlchemyService $alchemyService) {}

    /**
     * Build the Alchemy Amount preview payload for the validated Batch Crafting request.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $validated  The validated Batch Crafting request data.
     * @return array The lean Alchemy Amount preview payload.
     */
    public function build(Character $character, array $validated): array
    {
        $progress = $validated['progress'];
        $item = $this->findAlchemyItem($progress['alchemy_item_id']);

        if (is_null($item)) {
            return $this->buildUnavailableItemPreview($character, $progress);
        }

        $requestedAmount = $progress['alchemy_amount'];
        $disposition = BatchCraftingDisposition::from($validated['disposition']);
        $cost = $this->alchemyService->resolveCost($character, $item);
        $totalGoldDustCost = $cost['gold_dust'] * $requestedAmount;
        $totalShardsCost = $cost['shards'] * $requestedAmount;
        $goldDustAvailable = $character->gold_dust;
        $shardsAvailable = $character->shards;
        $canAffordGoldDust = $goldDustAvailable >= $totalGoldDustCost;
        $canAffordShards = $shardsAvailable >= $totalShardsCost;
        $bagCapacity = $this->resolveBagCapacity($character, $disposition);

        return [
            'item_id' => $item->id,
            'item_name' => $item->affix_name ?? $item->name,
            'requested_amount' => $requestedAmount,
            'gold_dust_cost_each' => $cost['gold_dust'],
            'shards_cost_each' => $cost['shards'],
            'total_gold_dust_cost' => $totalGoldDustCost,
            'total_shards_cost' => $totalShardsCost,
            'gold_dust_available' => $goldDustAvailable,
            'shards_available' => $shardsAvailable,
            'disposition' => $disposition->value,
            'listing_price' => $progress['listing_price'] ?? null,
            'alchemy_bag_capacity' => $bagCapacity,
            'blockers' => $this->buildBlockers($canAffordGoldDust, $canAffordShards, $bagCapacity),
        ];
    }

    /**
     * Resolve the requested Alchemy item, when it is still a valid craftable Alchemy item.
     *
     * @param  int  $itemId  The requested Alchemy item id.
     * @return Item|null The resolved item, or null when it is no longer valid.
     */
    private function findAlchemyItem(int $itemId): ?Item
    {
        return Item::where('id', $itemId)
            ->where('can_craft', true)
            ->where('crafting_type', 'alchemy')
            ->first();
    }

    /**
     * Resolve the character's current Alchemy Bag capacity, only relevant when the disposition retains the item.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  BatchCraftingDisposition  $disposition  The requested Alchemy disposition.
     * @return array{current: int, max: int, remaining: int}|null The Alchemy Bag capacity, or null when not retaining the item.
     */
    private function resolveBagCapacity(Character $character, BatchCraftingDisposition $disposition): ?array
    {
        if ($disposition !== BatchCraftingDisposition::KEEP) {
            return null;
        }

        $current = $character->getAlchemyBagCount();
        $max = $character->alchemy_bag_limit;

        return ['current' => $current, 'max' => $max, 'remaining' => max(0, $max - $current)];
    }

    /**
     * Build the blocking messages for the Alchemy Amount preview.
     *
     * @param  bool  $canAffordGoldDust  Whether the character can afford the requested Gold Dust cost.
     * @param  bool  $canAffordShards  Whether the character can afford the requested Shards cost.
     * @param  array{current: int, max: int, remaining: int}|null  $bagCapacity  The resolved Alchemy Bag capacity, if relevant.
     * @return array<int, string> The blocking messages, empty when nothing blocks the request.
     */
    private function buildBlockers(bool $canAffordGoldDust, bool $canAffordShards, ?array $bagCapacity): array
    {
        $blockers = [];

        if (! $canAffordGoldDust) {
            $blockers[] = 'You do not have enough Gold Dust to Alchemize this amount.';
        }

        if (! $canAffordShards) {
            $blockers[] = 'You do not have enough Shards to Alchemize this amount.';
        }

        if (! is_null($bagCapacity) && $bagCapacity['remaining'] < 1) {
            $blockers[] = 'Your Alchemy Bag does not have enough remaining space.';
        }

        return $blockers;
    }

    /**
     * Build the lean preview payload for a requested Alchemy item that is no longer valid.
     *
     * @param  Character  $character  The character requesting the preview.
     * @param  array  $progress  The requested Alchemy Amount progress data.
     * @return array The lean unavailable-item preview payload.
     */
    private function buildUnavailableItemPreview(Character $character, array $progress): array
    {
        return [
            'item_id' => $progress['alchemy_item_id'],
            'item_name' => null,
            'requested_amount' => $progress['alchemy_amount'],
            'gold_dust_cost_each' => 0,
            'shards_cost_each' => 0,
            'total_gold_dust_cost' => 0,
            'total_shards_cost' => 0,
            'gold_dust_available' => $character->gold_dust,
            'shards_available' => $character->shards,
            'disposition' => null,
            'listing_price' => $progress['listing_price'] ?? null,
            'alchemy_bag_capacity' => null,
            'blockers' => ['The selected item is no longer available to Alchemize.'],
        ];
    }
}
