<?php

namespace App\Game\Core\Controllers;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Values\ItemUsabilityType;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Jobs\CharacterBoonJob;
use App\Flare\Models\Item;
use App\Flare\Traits\Controllers\ItemsShowInformation;


class ItemsController extends Controller {

    use ItemsShowInformation;

    /**
     * @var CharacterAttackTransformer $characterAttackTransformer
     */
    private $characterAttackTransformer;

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * ItemsController constructor.
     *
     * @param CharacterAttackTransformer $characterAttackTransformer
     * @param Manager $manager
     */
    public function  __construct(CharacterAttackTransformer $characterAttackTransformer, Manager $manager) {
        $this->characterAttackTransformer = $characterAttackTransformer;
        $this->manager                    = $manager;
    }

    public function show(Item $item) {
        return $this->renderItemShow('game.items.show', $item);
    }

    public function useItem(Character $character, Item $item) {
        $slot = $character->inventory->slots->filter(function($slot) use($item) {
           return $slot->item_id === $item->id;
        })->first();

        if (is_null($slot)) {
            return redirect()->back()->with('error', 'You don\'t have this item.');
        }

        $type = null;

        if ($item->stat_increase) {
            $type = ItemUsabilityType::STAT_INCREASE;
        }

        if (!is_null($item->affects_skill_type)) {
            $type = ItemUsabilityType::EFFECTS_SKILL;
        }

        if ($item->damages_kingdoms) {
            $type = ItemUsabilityType::KINGDOM_DAMAGE;
        }
        dump($item->lasts_for);
        $completedAt = now()->addMinutes($item->lasts_for);
        dump(now()->toDateTime(), $completedAt->toDateTime());
        $boon = $character->boons()->create([
            'character_id'                             => $character->id,
            'type'                                     => $type,
            'stat_bonus'                               => $item->increase_stat_by,
            'affect_skill_type'                        => $item->affects_skill_type,
            'affected_skill_bonus'                     => $item->increase_skill_bonus_by,
            'affected_skill_training_bonus'            => $item->increase_skill_training_bonus_by,
            'affected_skill_base_damage_mod_bonus'     => null,
            'affected_skill_base_healing_mod_bonus'    => null,
            'affected_skill_base_ac_mod_bonus'         => null,
            'affected_skill_fight_time_out_mod_bonus'  => null,
            'affected_skill_move_time_out_mod_bonus'   => null,
            'started'                                  => now(),
            'complete'                                 => $completedAt,
        ]);

        CharacterBoonJob::dispatch($boon)->delay($completedAt);

        $character = $character->refresh();

        $characterAttack = new ResourceItem($character, $this->characterAttackTransformer);

        event(new UpdateAttackStats($this->manager->createData($characterAttack)->toArray(), $character->user));
        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'You used: ' . $item->name));

        $slot->delete();

        return redirect()->back()->with('success', 'Applied: ' . $item->name . ' for: ' . $item->lasts_for . ' Minutes.');
    }
}
