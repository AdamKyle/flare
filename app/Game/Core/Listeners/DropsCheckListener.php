<?php

namespace App\Game\Core\Listeners;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Services\CharacterService;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Adventure;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use Facades\App\Flare\Calculators\DropCheckCalculator;

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
        $lootingChance  = $event->character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        
        $canGetDrop     = DropCheckCalculator::fetchDropCheckChance($event->monster, $lootingChance, $event->adventure);

        if ($canGetDrop) {
            $drop = resolve(RandomItemDropBuilder::class)
                        ->setItemAffixes(ItemAffix::all())
                        ->generateItem($event->character);
            
            if (!is_null($drop->itemSuffix) || !is_null($drop->itemPrefix)) {
                $this->attemptToPickUpItem($event, $drop);
            }
        }
    }

    protected function attemptToPickUpItem(DropsCheckEvent $event, Item $item) {
        if ($event->character->inventory->slots->count() !== $event->character->inventory_max) {

            $event->character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $event->character->inventory->id,
            ]);

            event(new ServerMessageEvent($event->character->user, 'gained_item', $item->affix_name));
        } else {
            event(new ServerMessageEvent($event->character->user, 'inventory_full'));
        }
    }
}
