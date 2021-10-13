<?php

namespace App\Flare\Traits\Controllers;

use Auth;
use Illuminate\Contracts\View\View;
use App\Flare\Models\Adventure;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Skills\Values\SkillTypeValue;

trait ItemsShowInformation {

    /**
     * Renders show view.
     *
     * @param string $viewName
     * @param Item $item
     * @return View
     * @throws \Exception
     */
    public function renderItemShow(string $viewName, Item $item) {
        return view($viewName, $this->itemShowDetails($item));
    }

    /**
     * Get base details.
     *
     * @param Item $item
     * @return array
     * @throws \Exception
     */
    public function itemShowDetails(Item $item): array {
        $effects = 'N/A';
        $skills  = [];
        $skill   = null;

        if (!is_null($item->effect)) {
            $effect = new ItemEffectsValue($item->effect);

            if ($effect->walkOnWater()) {
                $effects = 'Walk on water';
            }

            if ($effect->labyrinth()) {
                $effects = 'Use Traverse (beside movement actions) to traverse to Labyrinth plane';
            }

            if ($effect->dungeon()) {
                $effects = 'Use Traverse (beside movement actions) to traverse to Dungeons plane';
            }

            if ($effect->walkOnDeathWater()) {
                $effects = 'Walk on Water (Aka: Death Water) in Dungeons Plane';
            }

            if ($effect->teleportToCelestial()) {
                $effects = 'Use /pct to find and teleport/traverse to the public Celestial Entity';
            }
        }

        if ($item->usable && !is_null($item->affects_skill_type)) {
            $type = new SkillTypeValue($item->affects_skill_type);

            $query = GameSkill::where('type', $item->affects_skill_type);

            if ($type->isTraining()) {
                $query = $query->where('can_train', true);
            }

            $skills = $query->pluck('name')->toArray();
        }

        if ($item->usable) {
            if (Auth::guest() || auth()->user()->hasRole('Admin')) {
                $skill = GameSkill::where('type', SkillTypeValue::ALCHEMY)->first();
            } else if (auth()->user()) {
                $skill = auth()->user()->character->skills->filter(function($skill) {
                    return $skill->type()->isAlchemy();
                })->first();
            }
        }

        return [
            'item'      => $item,
            'monster'   => Monster::where('quest_item_id', $item->id)->first(),
            'quest'     => Quest::where('item_id', $item->id)->orWhere('reward_item', $item->id)->first(),
            'location'  => Location::where('quest_reward_item_id', $item->id)->first(),
            'adventure' => Adventure::where('reward_item_id', $item->id)->first(),
            'effects'   => $effects,
            'skills'    => $skills,
            'skill'     => $skill,
        ];
    }
}
