<?php

namespace Tests\Setup;

use App\User;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateSkill;
use Tests\Traits\CreateItem;

class CharacterSetup {

    use CreateCharacter,
        CreateSkill,
        CreateRace,
        CreateClass,
        CreateItem;

    private $character;

    public function setupCharacter(User $user, array $options = []): CharacterSetup {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $this->character = $this->createCharacter([
            'name'          => 'Sample',
            'user_id'       => $user->id,
            'level'         => isset($options['level']) ? $options['level'] : 1,
            'xp'            => isset($options['xp']) ? $options['xp'] : 0,
            'can_attack'    => isset($options['can_attack']) ? $options['can_attack'] : true,
            'can_move'      => isset($options['can_move']) ? $options['can_move'] : true,
            'inventory_max' => 75,
            'gold'          => isset($options['gold']) ? $options['gold'] : 10,
        ]);

        // Create Empty Inventory:
        $this->character->inventory()->create([
            'character_id' => $this->character->id,
        ]);

        if (isset($options['fill_inventory'])) {
            $item = $this->createItem([
                'name'        => 'Rusty Dagger',
                'type'        => 'weapon',
                'base_damage' => '6'
            ]);

            if ($options['fill_inventory']) {
                $this->character->inventory->slots()->create([
                    'inventory_id' => $this->character->inventory->id,
                    'item_id'      => $item->id,
                    'equiped'      => false,
                ]);
            }
        }

        return $this;
    }

    public function giveItem(Item $item): CharacterSetup {

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equiped'      => false,
        ]);

        $this->character->refresh();

        return $this;
    }

    public function equipLeftHand(int $slotId = 1): CharacterSetup {
        $slot = $this->fetchSlot($slotId);

        $slot->update([
            'equipped' => true,
            'position' => 'left-hand',
        ]);

        $this->character->refresh();

        return $this;
    }

    public function equipRightHand(int $slotId = 1): CharacterSetup {
        $slot = $this->fetchSlot($slotId);

        $slot->update([
            'equipped' => true,
            'position' => 'right-hand',
        ]);

        $this->character->refresh();

        return $this;
    }

    public function setSkill(string $name, array $options): CharacterSetup {
        $this->createSkill([
            'character_id' => $this->character->id,
            'name' => $name,
            'level' => isset($options[strtolower($name).'_level']) ? $options[strtolower($name).'_level'] : 1,
            'skill_bonus' => isset($options[strtolower($name).'_bonus']) ? $options[strtolower($name).'_bonus'] : 0,
        ]);

        return $this;
    }

    public function getCharacter(): Character {
        return $this->character;
    }

    protected function fetchSlot(int $slotId): InventorySlot {
        $foundMatching = $this->character->inventory->slots->filter(function($slot) use($slotId) {
            return $slot->id === $slotId && !$slot->equipped;
         })->first();
 
         if (is_null($foundMatching)) {
             throw new \Exception('Item is not in inventory or is already equipped');
         }
 
         $slot = $this->character->inventory->slots->find($slotId);
 
         if (is_null($slot)) {
             throw new \Exception('Slot is not found, did you give the item to the player?');
         }

         return $slot;
    }

}
