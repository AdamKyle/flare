<?php

namespace App\Game\Battle\Listeners;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Battle\Events\DropsCheckEvent;
use App\Game\Battle\Services\CharacterService;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Drop;

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
        $canGetDrop    = (rand(1, 100) + $lootingChance) > $event->monster->drop_check * 10;

        if ($canGetDrop) {
            $drops = $event->monster->drops;

            if ($drops->isEmpty()) {
                return;
            }

            $drop = $this->getDrop($drops);

            $this->attemptToPickUpItem($event, $drop);
        }
    }

    protected function getDrop(Collection $drops) {
        if ($drops->count() === 1) {
            return $drops->first();
        } else {
            return $drops[rand(0, $drops->count() - 1)];
        }
    }

    protected function attemptToPickUpItem(DropsCheckEvent $event, Drop $drop) {
        if ($event->character->inventory->slots->count() !== $event->character->inventory_max) {
            $event->character->inventory->slots()->create([
                'item_id'      => $drop->item_id,
                'inventory_id' => $event->character->inventory->id,
            ]);

            event(new ServerMessageEvent($event->character->user, 'gained_item', $drop->item->name));
        } else {
            event(new ServerMessageEvent($event->character->user, 'inventory_full'));
        }
    }
}
