<?php

namespace App\Game\Core\Services;


use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Values\RandomAffixDetails;
use Illuminate\Support\Collection;

class RandomEnchantmentService {

    private $randomAffixGenerator;

    public function __construct(RandomAffixGenerator $randomAffixGenerator) {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

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

    public function fetchUniquesFromCharactersInventory(Character $character): Collection {
        return $character->inventory->slots->filter(function($slot) {
            if (!$slot->equipped && $slot->item->type !== 'quest') {
                if (!is_null($slot->item->itemPrefix)) {
                    if ($slot->item->itemPrefix->randomly_generated) {
                        return $slot;
                    }
                }
            }

            if (!$slot->equipped && $slot->item->type !== 'quest') {
                if (!is_null($slot->item->itemSuffix)) {
                    if ($slot->item->itemSuffix->randomly_generated) {
                        return $slot;
                    }
                }
            }
        })->values();
    }

    public function fetchNonUniqueItems(Character $character): Collection {
        return $character->inventory->slots->filter(function($slot) {
            if (!$slot->equipped && $slot->item->type !== 'quest' && $slot->item->type !== 'alchemy') {
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

                return $slot;
            }
        })->values();
    }

    protected function generateRandomAffixForRandom(Character $character, int $amount): Item {
        $item = ItemModel::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('cost', '<=', 4000000000)
            ->inRandomOrder()
            ->first();

        $randomAffix = $this->randomAffixGenerator
            ->setCharacter($character)
            ->setPaidAmount($amount);

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if (rand(1, 100) > 50) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id
            ]);
        }

        return $duplicateItem;
    }
}
