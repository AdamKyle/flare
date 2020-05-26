<?php

namespace App\Game\Battle\Listeners;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Battle\Events\DropsCheckEvent;
use App\Game\Battle\Services\CharacterService;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;

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
        $canGetDrop    = (rand(1, 100) + $lootingChance) > (100 - $event->monster->drop_check);

        if ($canGetDrop) {
            $drop = resolve(RandomItemDropBuilder::class)
                        ->setItemAffixes(ItemAffix::all())
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

            event(new ServerMessageEvent($event->character->user, 'gained_item', $item->name));
        } else {
            event(new ServerMessageEvent($event->character->user, 'inventory_full'));
        }
    }
}
