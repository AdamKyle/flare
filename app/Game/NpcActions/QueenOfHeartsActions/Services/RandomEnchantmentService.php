<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Services;


use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\RandomAffixDetails;
use Exception;
use Illuminate\Support\Collection;

class RandomEnchantmentService {

    /**
     * @var RandomAffixGenerator $randomAffixGenerator
     */
    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator) {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    /**
     * Generate for type.
     *
     * @param Character $character
     * @param string $type
     * @return Item
     * @throws Exception
     */
    public function generateForType(Character $character, string $type): Item {
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
     *
     * @param string $type
     * @return int
     */
    public function getCost(string $type): int {
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
     *
     * @param Character $character
     * @return Collection
     */
    public function fetchUniquesFromCharactersInventory(Character $character): Collection {
        return $character->inventory->slots->filter(function($slot) {
            if (!$slot->equipped && ($slot->item->type !== 'quest' && $slot->item->type !== 'alchemy' && $slot->item->type !== 'trinket')) {
                if (!is_null($slot->item->itemPrefix)) {
                    if ($slot->item->itemPrefix->randomly_generated) {
                        return $slot;
                    }
                }
            }

            if (!$slot->equipped && ($slot->item->type !== 'quest' && $slot->item->type !== 'alchemy' && $slot->item->type !== 'trinket')) {
                if (!is_null($slot->item->itemSuffix)) {
                    if ($slot->item->itemSuffix->randomly_generated) {
                        return $slot;
                    }
                }
            }
        })->values();
    }

    /**
     * Fetch Api data.
     *
     * @param Character $character
     * @return array
     */
    public function fetchDataForApi(Character $character): array {
        $uniqueSlots    = $this->fetchUniquesFromCharactersInventory($character);
        $nonUniqueSlots = $this->fetchNonUniqueItems($character);

        return [
            'unique_slots'     => $uniqueSlots,
            'non_unique_slots' => $nonUniqueSlots,
        ];
    }

    /**
     * Fetch non unique items.
     *
     * @param Character $character
     * @return Collection
     */
    public function fetchNonUniqueItems(Character $character): Collection {
        return $character->inventory->slots->filter(function($slot) {
            if (!$slot->equipped &&
                $slot->item->type !== 'quest' &&
                $slot->item->type !== 'alchemy' &&
                $slot->item->type !== 'trinket' &&
                $slot->item->type !== 'artifact')
            {
                if (!is_null($slot->item->itemPrefix)) {
                    if (!$slot->item->itemPrefix->randomly_generated) {
                        return $slot;
                    }
                }

                if (!is_null($slot->item->itemSuffix)) {
                    if (!$slot->item->itemSuffix->randomly_generated) {
                        return $slot;
                    }
                }
            }
        })->values();
    }

    /**
     * Check if player is in hell.
     *
     * @param Character $character
     * @return bool
     */
    public function isPlayerInHell(Character $character): bool {
        return $character->inventory->slots->filter(function($slot) {
            return $slot->item->effect === ItemEffectsValue::QUEEN_OF_HEARTS;
        })->isNotEmpty() && $character->map->gameMap->mapType()->isHell();
    }

    /**
     * @return bool
     */
    protected function shouldAddSuffixToItem(): bool {
        return rand(1, 100) > 50;
    }

    /**
     * Generate completely random affix.
     *
     * @param Character $character
     * @param int $amount
     * @return Item
     * @throws Exception
     */
    protected function generateRandomAffixForRandom(Character $character, int $amount): Item {
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
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id
            ]);
        }

        $duplicateItem->update([
            'market_sellable' => true,
        ]);

        return $duplicateItem;
    }
}
