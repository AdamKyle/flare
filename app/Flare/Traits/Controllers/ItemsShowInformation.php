<?php

namespace App\Flare\Traits\Controllers;

use App\Flare\Models\Adventure;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Flare\Values\ItemEffectsValue;

trait ItemsShowInformation {

    public function renderItemShow(string $viewName, Item $item) {
        $effects = 'N/A';
        $skills  = [];

        if (!is_null($item->effects)) {
            $effect = new ItemEffectsValue($item->effect);

            if ($effect->walkOnWater()) {
                $effects = 'Walk on water';
            }

            if ($effect->labyrinth()) {
                $effects = 'Use Traverse (beside movement actions) to traverse to Labyrinth plane';
            }
        }

        if ($item->usable) {
            $skills = GameSkill::where('type', $item->affects_skill_type)->pluck('name')->toArray();
        }

        return view('game.items.item', [
            'item'      => $item,
            'monster'   => Monster::where('quest_item_id', $item->id)->first(),
            'quest'     => Quest::where('item_id', $item->id)->first(),
            'location'  => Location::where('quest_reward_item_id', $item->id)->first(),
            'adventure' => Adventure::where('reward_item_id', $item->id)->first(),
            'effects'   => $effects,
            'skills'    => $skills,
        ]);
    }
}
