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
                $effects = 'Walk on water (Surface and Labyrinth)';
            }

            if ($effect->labyrinth()) {
                $effects = 'Use Traverse (beside movement map-actions) to traverse to Labyrinth plane';
            }

            if ($effect->dungeon()) {
                $effects = 'Use Traverse (beside movement map-actions) to traverse to Dungeons plane';
            }

            if ($effect->hell()) {
                $effects = 'Use Traverse (beside movement map-actions) to traverse to Hell plane';
            }

            if ($effect->purgatory()) {
                $effects = 'Use Traverse (beside movement map-actions) to traverse to Purgatory plane (only while in Hell at the location: Tear in the Fabric of Time (X/Y: 208/64))';
            }

            if ($effect->canMassEmbezzle()) {
                $effects = 'Lets you mass embezzle from all kingdoms on the plane. Simply go to your kingdoms tab, click a kingdom and see the new Mass Embezzle option. Does not work cross plane.';
            }

            if ($effect->walkOnMagma()) {
                $effects = 'Lets you walk on Magma in Hell.';
            }

            if ($effect->areAffixesIrresistible()) {
                $effects = 'Makes affix damage irresistible except in Hell and Purgatory.';
            }

            if ($effect->canSpeakToQueenOfHearts()) {
                $effects = 'Lets a character approach and speak to the Queen of Hearts in Hell.';
            }

            if ($effect->isGoldDustRush()) {
                $effects = 'Provides a small chance for the player to get a gold dust rush when disenchanting.';
            }

            if ($effect->walkOnDeathWater()) {
                $effects = 'Walk on Death Water in Dungeons Plane';
            }

            if ($effect->teleportToCelestial()) {
                $effects = 'Use /pct to find and teleport/traverse to the public Celestial Entity';
            }

            if ($effect->effectsFactionPoints()) {
                $effects = 'Instead of gaining 2 points, you will now gain 10 points per kill. This only applies starting at level one of the faction.';
            }

            if ($effect->getCopperCoins()) {
                $effects = 'Enemies in Purgatory will now start dropping copper coins in relation to their gold amounts. These amounts are random between 5-20 per battle.';
            }

            if ($effect->canEnterPurgatorySmithHouse()) {
                $effects = 'You can enter the Purgatory Smith house in Purgatory to investigate the Green Growing Light in the basement.';
            }

            if ($effect->hideChatLocation()) {
                $effects = 'Hides your location from chat so others cannot find you to duel you!';
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
            'effects'   => $effects,
            'skills'    => $skills,
            'skill'     => $skill,
        ];
    }
}
