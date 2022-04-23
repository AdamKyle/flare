<?php

namespace App\Game\Core\Services;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\ItemUsabilityType;
use App\Game\Core\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Jobs\CharacterBoonJob;
use App\Game\Messages\Events\ServerMessageEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class UseItemService {

    private $characterAttackTransformer;

    private $manager;

    public function __construct(Manager $manager, CharacterSheetBaseInfoTransformer $characterAttackTransformer) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
    }

    public function useItem(InventorySlot $slot, Character $character, Item $item) {
        $completedAt = now()->addMinutes($item->lasts_for);

        $boon = $character->boons()->create([
            'character_id'             => $character->id,
            'item_id'                  => $slot->item->id,
            'started'                  => now(),
            'complete'                 => $completedAt,
        ]);

        CharacterBoonJob::dispatch($boon->id)->delay($completedAt);

        $slot->delete();
    }

    /**
     * Removes a boon from the character and updates their info.
     *
     * @param Character $character
     * @param CharacterBoon $boon
     */
    public function removeBoon(Character $character, CharacterBoon $boon) {
        $boon->delete();

        $character = $character->refresh();

        $this->updateCharacter($character);
    }

    public function updateCharacter(Character $character, Item $item = null) {
        resolve(BuildCharacterAttackTypes::class)->buildCache($character->refresh());

        $characterAttack = new ResourceItem($character, $this->characterAttackTransformer);

        event(new UpdateBaseCharacterInformation($character->user, $this->manager->createData($characterAttack)->toArray()));
        event(new UpdateTopBarEvent($character));

        if (!is_null($item)) {
            event(new ServerMessageEvent($character->user, 'You used: ' . $item->name));
        }

        $boons = $character->boons->toArray();

        foreach ($boons as $key => $boon) {
            $skills = GameSkill::where('type', $boon['affect_skill_type'])->pluck('name')->toArray();

            $boon['type'] = (new ItemUsabilityType($boon['type']))->getNamedValue();
            $boon['affected_skills'] = implode(', ', $skills);

            $boons[$key] = $boon;
        }

        event(new CharacterBoonsUpdateBroadcastEvent($character->user, $boons));
    }

    protected function getType(Item $item): int {
        $type = ItemUsabilityType::OTHER;

        if ($item->stat_increase) {
            $type = ItemUsabilityType::STAT_INCREASE;
        }

        if (!is_null($item->affects_skill_type)) {
            $type = ItemUsabilityType::EFFECTS_SKILL;
        }

        return $type;
    }
}
