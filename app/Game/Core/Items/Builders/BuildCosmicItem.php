<?php

namespace App\Game\Core\Items\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Items\Values\RandomAffixTier;
use Exception;

class BuildCosmicItem
{
    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator)
    {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    /**
     * Build Mythic Item for winner. When given, `$onlyTypes` narrows the
     * candidate catalog Item to exactly those types (e.g. socket-eligible
     * equipment only) instead of every non-excluded type.
     *
     *
     * @param Character $character
     * @param array $onlyTypes
     * @return Item
     *
     * @throws Exception
     */
    public function fetchCosmicItem(Character $character, array $onlyTypes = []): Item
    {
        $prefix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixTier::COSMIC->value)
            ->generateAffix('prefix');

        $suffix = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixTier::COSMIC->value)
            ->generateAffix('suffix');

        $item = Item::inRandomOrder()
            ->doesntHave('itemSuffix')
            ->doesntHave('itemPrefix')
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->when(! empty($onlyTypes), fn ($query) => $query->whereIn('type', $onlyTypes))
            ->whereNull('specialty_type')
            ->first();

        $item = $item->duplicate();

        $item->update([
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
            'is_mythic' => true,
        ]);

        return $item->refresh();
    }
}
