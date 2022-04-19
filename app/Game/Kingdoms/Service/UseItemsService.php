<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateNotificationsBroadcastEvent;
use App\Game\Kingdoms\Events\UpdateKingdomLogs;
use App\Game\Kingdoms\Handlers\KingdomHandler;
use App\Game\Kingdoms\Handlers\NotifyHandler;
use App\Game\Messages\Events\GlobalMessageEvent;

class UseItemsService  {

    private KingdomHandler $kingdomHandler;

    private NotifyHandler $notifyHandler;

    private Kingdom $oldKingdom;

    private $damageToKingdom = 0.0;

    private $kingdomDefence  = 0.0;

    private $defender        = null;

    public function  __construct(KingdomHandler $kingdomHandler, NotifyHandler $notifyHandler) {
        $this->kingdomHandler = $kingdomHandler;
        $this->notifyHandler  = $notifyHandler;
    }

    public function useItems(Character $character, Kingdom $defendingKingdom, array $slotIds) {
        $this->oldKingdom = $defendingKingdom;

        $this->removeItemsFromCharacter($character, $slotIds);
        $this->setDefender($defendingKingdom);
        $this->setKingdomDefence($defendingKingdom);
        $this->setDamageToKingdom();

        $defendingKingdom = $this->damageBuildings($defendingKingdom);
        $defendingKingdom = $this->damageUnits($defendingKingdom);
        $defendingKingdom = $this->moraleChange($defendingKingdom);

        if (!is_null($this->defender)) {
            $this->createAttackLog($defendingKingdom);
        }

        if ($this->damageToKingdom > 0.0) {
            $message = $character->name . ' has caused the earth to shake, the buildings to crumble and the units to be slaughtered at: ' .
                $defendingKingdom->name . ' (kingdom) doing: ' . ($this->damageToKingdom * 100) . '% damage to units and buildings, on the ' . $defendingKingdom->gameMap->name . ' plane. Even The Creator trembles in fear.';
        } else {
            $message = 'The defender of: ' . $defendingKingdom->name . ' (kingdom) on the ' . $defendingKingdom->gameMap->name .
                ' plane laughs at the attempts of ' . $character->name . ' to rain down death and devastation. Even the people openly mock them in the streets!';
        }

        broadcast(new GlobalMessageEvent($message));
    }

    protected function removeItemsFromCharacter(Character $character, array $slotIds) {
        $slots = $character->inventory->slots()->whereIn('id', $slotIds)->get();

        foreach ($slots as $slot) {
            $this->damageToKingdom += $slot->item->kingdom_damage;

            $slot->delete();
        }

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'usable_items'));

        event(new CharacterInventoryDetailsUpdate($character->user));
    }

    protected function setKingdomDefence(Kingdom $defendingKingdom) {
        $this->kingdomDefence = $defendingKingdom->fetchKingdomDefenceBonus();
    }

    protected function setDamageToKingdom() {

        if ($this->kingdomDefence > $this->damageToKingdom) {
            $this->damageToKingdom = 0.0;
        } else {
            $this->damageToKingdom -= $this->kingdomDefence;
        }
    }

    protected function setDefender(Kingdom $defendingKingdom) {
        $this->defender = $defendingKingdom->character;
    }

    protected function damageBuildings(Kingdom $defendingKingdom): Kingdom {
        foreach ($defendingKingdom->buildings as $building) {
            $newDurability =  round($building->current_durability - ($building->current_durability * $this->damageToKingdom));

            if ($newDurability < 0) {
                $newDurability = 0;
            }

            $building->update([
                'current_durability' => $newDurability,
            ]);
        }

        return $defendingKingdom->refresh();
    }

    protected function damageUnits(Kingdom $defendingKingdom): Kingdom {
        foreach ($defendingKingdom->units as $unit) {
            $newAmount = round($unit->amount - ($unit->amount * $this->damageToKingdom));

            if ($newAmount < 0) {
                $newAmount = 0;
            }

            $unit->update([
                'amount' => $newAmount
            ]);
        }

        return $defendingKingdom->refresh();
    }

    protected function moraleChange(Kingdom $defendingKingdom): Kingdom {
        return $this->kingdomHandler->setKingdom($defendingKingdom)
            ->decreaseMorale()
            ->getKingdom();
    }

    protected function createAttackLog(Kingdom $defendingKingdom) {
        $log = KingdomLog::create([
            'character_id'    => $this->defender->id,
            'status'          => KingdomLogStatusValue::BOMBS_DROPPED,
            'old_defender'    => $this->oldKingdom,
            'new_defender'    => $defendingKingdom->toArray(),
            'to_kingdom_id'   => $defendingKingdom->id,
            'published'       => true,
        ]);

        $message = 'Your kingdom ' . $defendingKingdom->name . ' at (X/Y) ' . $defendingKingdom->x_position . '/' . $defendingKingdom->y_position . ' Had items dropped on it!';

        $this->notifyHandler->createNotificationEvent($this->defender, $log, $message, 'failed', 'Items dropped!');

        event(new UpdateNotificationsBroadcastEvent($this->defender->refresh()->notifications()->where('read', false)->get(), $this->defender->user));

        event(new UpdateKingdomLogs($this->defender->refresh()));

        $message = 'Your kingdom ' . $defendingKingdom->name . ' at (X/Y) ' . $defendingKingdom->x_position .
            '/' . $defendingKingdom->y_position . ' on the ' .
            $defendingKingdom->gameMap->name . ' plane, has had an item dropped on it doing: ' . ($this->damageToKingdom * 100) . '% to Buildings and Units. Check your Attack logs for more info!';

        $this->notifyHandler->sendMessage($this->defender->user, 'kingdom-attacked', $message);
    }
}
