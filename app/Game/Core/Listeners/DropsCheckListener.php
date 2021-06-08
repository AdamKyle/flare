<?php

namespace App\Game\Core\Listeners;

use App\Game\Core\Events\DropsCheckEvent;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Flare\Calculators\DropCheckCalculator;

class DropsCheckListener
{

    /**
     * Handle the event.
     *
     * @param DropsCheckEvent $event
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

        if (!is_null($event->monster->quest_item_id)) {
            $canGetQuestItem = DropCheckCalculator::fetchQuestItemDropCheck($event->monster, $lootingChance, $event->adventure);

            if ($canGetQuestItem) {
                $this->attemptToPickUpItem($event, $event->monster->questItem);
            }
        }
    }

    protected function attemptToPickUpItem(DropsCheckEvent $event, Item $item) {
        if ($event->character->inventory->slots->count() !== $event->character->inventory_max) {

            $alreadyHas = $event->character->inventory->slots->filter(function ($slot) use ($item) {
                return $slot->item_id === $item->id && $item->type === 'quest';
            })->all();

            if (empty($alreadyHas)) {
                $event->character->inventory->slots()->create([
                    'item_id' => $item->id,
                    'inventory_id' => $event->character->inventory->id,
                ]);

                if (!is_null($item->effect)) {
                    $message = $event->character->name . ' has found: ' . $item->affix_name;

                    broadcast(new GlobalMessageEvent($message));
                }

                event(new ServerMessageEvent($event->character->user, 'gained_item', $item->affix_name));
            }
        } else {
            event(new ServerMessageEvent($event->character->user, 'inventory_full'));
        }
    }
}
