<?php

namespace App\Game\Battle\Listeners;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Battle\Events\DropsCheckEvent;
use App\Game\Battle\Services\CharacterService;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Item;

class DropsCheckListener
{

    private $characterService;

    public function __construct(CharacterService $characterService) {
        $this->characterService = $characterService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(DropsCheckEvent $event)
    {
        $lootingChance = $event->character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $canGetDrop    = (rand(1, 100) + $lootingChance) > 1; //(100 - $event->monster->drop_check);

        if ($canGetDrop) {
            $drop = resolve(RandomItemDropBuilder::class)
                        ->setItemAffixes(config('game.item_affixes'))
                        ->setArtifactProperties(config('game.artifact_properties'))
                        ->generateItem($event->character);

            $this->attemptToPickUpItem($event, $drop);
        }
    }

    protected function attemptToPickUpItem(DropsCheckEvent $event, Item $item) {
        if ($event->character->inventory->slots->count() !== $event->character->inventory_max) {
            $event->character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $event->character->inventory->id,
            ]);

            $itemName = $item->name;

            if (!is_null($item->itemAffix)) {
                if ($item->itemAffix->type === 'suffix') {
                    $itemName = $item->name . ' *'.$item->itemAffix->name.'*';
                }

                if ($item->itemAffix->type === 'prefix') {
                    $itemName = '*'.$item->itemAffix->name.'* ' . $item->name;
                }
            }

            event(new ServerMessageEvent($event->character->user, 'gained_item', $itemName));
        } else {
            event(new ServerMessageEvent($event->character->user, 'inventory_full'));
        }
    }
}
