<?php

namespace App\Flare\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\RandomAffixDetails;
use Exception;

class BuildUniqueItem
{
    private RandomAffixGenerator $randomAffixGenerator;

    public function __construct(RandomAffixGenerator $randomAffixGenerator)
    {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    /**
     * Build Mythic Item for winner.
     *
     * @throws Exception
     */
    public function fetchUniqueItem(Character $character): Item
    {
        $prefix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::LEGENDARY)
            ->generateAffix('prefix');

        $suffix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::LEGENDARY)
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
        ]);

        return $item->refresh();
    }
}
