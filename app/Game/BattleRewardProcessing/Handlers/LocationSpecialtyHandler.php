<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Values\RandomAffixDetails;

class LocationSpecialtyHandler {

    private RandomAffixGenerator $randomAffixGenerator;

    public function __construct(RandomAffixGenerator $randomAffixGenerator) {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    public function handleMonsterFromSpecialLocation(Character $character, Monster $monster): void {
        $this->giveItemReward($character);
    }

    private function giveItemReward(Character $character): void {
        $item = $this->giveCharacterRandomCosmicItem($character);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);
    }

    private function giveCharacterRandomCosmicItem(Character $character): Item {
        $item = Item::where('cost', '<=', RandomAffixDetails::COSMIC)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNull('specialty_type')
            ->whereNotIn('type', ['alchemy', 'quest', 'trinket', 'artifact'])
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();


        $randomAffix = $this->randomAffixGenerator
            ->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::COSMIC);

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        // @codeCoverageIgnoreStart
        if (rand(1, 100) > 50) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id
            ]);
        }
        // @codeCoverageIgnoreEnd

        $duplicateItem->update([
            'is_cosmic' => true,
        ]);

        return $duplicateItem->refresh();
    }
}
