<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\RandomAffixDetails;
use Exception;

class BuildMythicItem {

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
     * Build Mythic Item for winner.
     *
     * @param Character $character
     * @return Item
     * @throws Exception
     */
    public function fetchMythicItem(Character $character): Item {
        $prefix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::MYTHIC)
            ->generateAffix('prefix');

        $suffix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::MYTHIC)
            ->generateAffix('suffix');

        $item = Item::inRandomOrder()
                    ->doesntHave('itemSuffix')
                    ->doesntHave('itemPrefix')
                    ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
                    ->whereNull('specialty_type')
                    ->first();

        $item = $item->duplicate();

        $item->update([
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
            'is_mythic'      => true,
        ]);

        return $item->refresh();
    }
}
