<?php

namespace App\Game\Core\Services;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Values\ItemUsabilityType;
use App\Game\Core\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Jobs\CharacterBoonJob;
use App\Game\Messages\Events\ServerMessageEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class UseItemService {

    private $characterAttackTransformer;

    private $manager;

    public function __construct(Manager $manager, CharacterAttackTransformer $characterAttackTransformer) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
    }

    public function useItem(InventorySlot $slot, Character $character, Item $item) {
        $completedAt = now()->addMinutes($item->lasts_for);

        $boon = $character->boons()->create([
            'character_id'                             => $character->id,
            'type'                                     => $this->getType($item),
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

        CharacterBoonJob::dispatch($boon->id)->delay($completedAt);

        $character = $character->refresh();

        $this->updateCharacter($character, $item);

        $slot->delete();
    }

    public function updateCharacter(Character $character, Item $item = null) {
        $characterAttack = new ResourceItem($character, $this->characterAttackTransformer);

        event(new UpdateAttackStats($this->manager->createData($characterAttack)->toArray(), $character->user));
        event(new UpdateTopBarEvent($character));

        if (!is_null($item)) {
            event(new ServerMessageEvent($character->user, 'You used: ' . $item->name));
        }

        $boons = $character->boons->toArray();

        foreach ($boons as $key => $boon) {
            $skills = GameSkill::where('type', $boon['affect_skill_type'])->pluck('name')->toArray();

            $boon['type'] = (new ItemUsabilityType($boon['type']))->getNamedValue();
            $boon['affected_skills'] = implode(',', $skills);

            $boons[$key] = $boon;
        }

        event(new CharacterBoonsUpdateBroadcastEvent($character->user, $boons));
    }

    protected function getType(Item $item): int {
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

        return $type;
    }
}
