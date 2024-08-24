<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Services;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\RandomAffixDetails;
use Exception;
use Illuminate\Support\Collection;

class RandomEnchantmentService
{
    private RandomAffixGenerator $randomAffixGenerator;

    public function __construct(RandomAffixGenerator $randomAffixGenerator)
    {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    /**
     * Generate for type.
     *
     * @throws Exception
     */
    public function generateForType(Character $character, string $type): Item
    {
        switch ($type) {
            case 'medium':
                return $this->generateRandomAffixForRandom($character, RandomAffixDetails::MEDIUM);
            case 'legendary':
                return $this->generateRandomAffixForRandom($character, RandomAffixDetails::LEGENDARY);
            case 'basic':
            default:
                return $this->generateRandomAffixForRandom($character, RandomAffixDetails::BASIC);
        }
    }

    /**
     * Get cost of unique.
     */
    public function getCost(string $type): int
    {
        switch ($type) {
            case 'medium':
                return RandomAffixDetails::MEDIUM;
            case 'legendary':
                return RandomAffixDetails::LEGENDARY;
            case 'basic':
            default:
                return RandomAffixDetails::BASIC;
        }
    }

    /**
     * Fetch uniques from characters inventory.
     */
    public function fetchUniquesFromCharactersInventory(Character $character): Collection
    {
        return $character->inventory->slots->filter(function ($slot) {
            $item = $slot->item;

            $item->load('itemPrefix', 'itemSuffix');

            if (! $slot->equipped && ($item->is_mythic || $item->is_unique || $item->is_cosmic)) {
                // Check if item has a prefix and it's randomly generated
                if ($item->itemPrefix && $item->itemPrefix->randomly_generated) {
                    return true;
                }
                // Check if item has a suffix and it's randomly generated
                if ($item->itemSuffix && $item->itemSuffix->randomly_generated) {
                    return true;
                }
            }
        })->values();
    }

    /**
     * Fetch Api data.
     */
    public function fetchDataForApi(Character $character): array
    {
        $uniqueSlots = $this->fetchUniquesFromCharactersInventory($character);
        $nonUniqueSlots = $this->fetchNonUniqueItems($character);

        return [
            'unique_slots' => $uniqueSlots,
            'non_unique_slots' => $nonUniqueSlots,
        ];
    }

    /**
     * Fetch non unique items.
     */
    public function fetchNonUniqueItems(Character $character): Collection
    {
        return $character->inventory->slots->filter(function ($slot) {
            if (
                ! $slot->equipped &&
                $slot->item->type !== 'quest' &&
                $slot->item->type !== 'alchemy' &&
                $slot->item->type !== 'trinket' &&
                $slot->item->type !== 'artifact' &&
                ! $slot->item->is_mythic &&
                ! $slot->item->is_cosmic &&
                ! $slot->item->is_unique
            ) {
                if (! is_null($slot->item->itemPrefix)) {
                    if (! $slot->item->itemPrefix->randomly_generated) {
                        return $slot;
                    }
                }

                if (! is_null($slot->item->itemSuffix)) {
                    if (! $slot->item->itemSuffix->randomly_generated) {
                        return $slot;
                    }
                }

                return $slot;
            }
        })->values();
    }

    /**
     * Check if player is in hell.
     */
    public function isPlayerInHell(Character $character): bool
    {
        return $character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::QUEEN_OF_HEARTS;
        })->isNotEmpty() && $character->map->gameMap->mapType()->isHell();
    }

    protected function shouldAddSuffixToItem(): bool
    {
        return rand(1, 100) > 50;
    }

    /**
     * Generate completely random affix.
     *
     * @throws Exception
     */
    protected function generateRandomAffixForRandom(Character $character, int $amount): Item
    {
        $item = Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNotIn('type', ['alchemy', 'quest', 'trinket', 'artifact'])
            ->where('cost', '<=', 2000000000)
            ->inRandomOrder()
            ->first();

        $randomAffix = $this->randomAffixGenerator
            ->setCharacter($character)
            ->setPaidAmount($amount);

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if ($this->shouldAddSuffixToItem()) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id,
            ]);
        }

        $duplicateItem->update([
            'market_sellable' => true,
        ]);

        return $duplicateItem;
    }
}
