<?php

namespace Tests\Setup;

use App\Flare\Models\Adventure;
use App\Flare\Models\User;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Game\Core\Services\CharacterService;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMap;
use Tests\Traits\CreateGameSkill;

class CharacterSetup {

    use CreateCharacter,
        CreateSkill,
        CreateRace,
        CreateClass,
        CreateItem,
        CreateMap,
        CreateGameSkill;

    private $character;

    public function setupCharacter(User $user, array $options = [], array $classOptions = []): CharacterSetup {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass($classOptions);

        $this->character = $this->createCharacter([
            'name'          => isset($options['name']) ? $options['name'] : 'Sample',
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

    public function givePlayerLocation(int $x = 16, int $y =16): CharacterSetup {

        $this->createMap([
            'character_id'         => $this->character->id,
            'position_x'           => $x,
            'position_y'           => $y,
            'character_position_x' => $x,
            'character_position_y' => $y,
            'game_map_id'          => GameMap::create([
                'name'    => 'Sample',
                'path'    => 'path',
                'default' => true,
            ])->id,
        ]);
        
        return $this;
    }

    public function levelCharacterUp(int $levels = 1): CharacterSetup {
        $characterService = new CharacterService();

        for ($i = 0; $i <= $levels; $i++) {
            $characterService->levelUpCharacter($this->character);

            $this->character->refresh();
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

    public function equipSpellSlot(int $slotId = 1, string $position = 'spell-one'): CharacterSetup {
        $slot = $this->fetchSlot($slotId);

        $slot->update([
            'equipped' => true,
            'position' => $position,
        ]);

        $this->character->refresh();

        return $this;
    }

    public function equipArtifact(int $slotId = 1, string $position = 'artifact-one'): CharacterSetup {
        $slot = $this->fetchSlot($slotId);

        $slot->update([
            'equipped' => true,
            'position' => $position,
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

    public function setSkill(string $name, array $baseOptions = [], array $skillOptions = [], bool $currentlyTraining = false): CharacterSetup {
        
        if ($currentlyTraining) {
            $found = $this->character->skills->filter(function($skill) {
                return $skill->currently_training;
            })->first();

            if (!is_null($found)) {
                throw new \Exception('You already have a skill set as currently training: ' . $found->name);
            } else {
                if (isset($options['xp_towards'])) {
                    throw new \Exception("you forgot to add xp_towards as an option for this skill: " . $found->name);
                }
            }
        }

        $gameSkill = $this->createGameSkill(array_merge([
            'name'         => $name,
            'description'  => 'sample',
        ], $baseOptions));
        
        $this->createSkill(array_merge([
            'character_id'  => $this->character->id,
            'game_skill_id' => $gameSkill->id, 
        ], $skillOptions));

        $this->character->refresh();

        return $this;
    }

    public function createAdventureLog(Adventure $adventure, array $options = []): CharacterSetup {

        $log = array_merge([
            'character_id' => $this->character->id,
            'adventure_id' => $adventure->id,
        ], $options);

        $this->character->adventureLogs()->create($log);

        $this->character->refresh();

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
