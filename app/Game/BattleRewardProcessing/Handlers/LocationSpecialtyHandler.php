<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

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

        $character = $character->refresh();

        $slot = $character->inventory->slots->where('item_id', '=', $item->id)->first();

        event(new GlobalMessageEvent($character->name . ' Has slaughtered a beast beyond comprehension and been rewarded with a cosmic gift!'));

        event(new ServerMessageEvent($character->user, 'You have received a cosmic item! How exciting! Rewarded with: ' . $slot->item->affix_name, $slot->id));
    }

    private function giveCharacterRandomCosmicItem(Character $character): Item {
        $item = Item::where('specialty_type', ItemSpecialtyType::DELUSIONAL_SILVER)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
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
