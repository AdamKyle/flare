<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Game\Core\Services\CharacterService;
use Str;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMap;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateSkill;
use Tests\Traits\CreateUser;

class CharacterFactory {

    use CreateRace,
        CreateClass,
        CreateCharacter,
        CreateUser,
        CreateItem,
        CreateMap,
        CreateGameMap,
        CreateGameSkill,
        CreateSkill;

    private $character;

    /**
     * Creates a base character associated with a user.
     * 
     * This base character will also have:
     * 
     * - Core inventory - empty.
     * - Base Skills: Accuracy, Looting and Dodge.
     * 
     * @param array $raceOptions
     * @param array $classOptions
     * @return CharacterFactory
     */
    public function createBaseCharacter(array $raceOptions = [], array $classOptions = []): CharacterFactory {
        $race  = $this->createRace($raceOptions);
        $class = $this->createClass($classOptions);
        $user  = $this->createUser();

        $this->character = $this->createCharacter([
            'name'          => Str::random(10),
            'user_id'       => $user->id,
            'level'         => 1,
            'xp'            => 0,
            'can_attack'    => true,
            'can_move'      => true,
            'inventory_max' => 75,
            'gold'          => 10,
            'game_class_id' => $class->id,
            'game_race_id'  => $race->id,
        ]);

        $this->createInventory();

        $this->assignBaseSkills();

        return $this;
    }

    /**
     * Lets you update the character
     * 
     * @param array $changes | []
     * @return CharacterFactory
     */
    public function updateCharacter(array $changes = []): CharacterFactory {
        $this->character->update($changes);

        return $this;
    }

    /**
     * Equip the starting equipment.
     * 
     * All characters will be equipped with a rusty bloody broken dagger.
     * 
     * @return CharacterFactory
     */
    public function equipStartingEquipment(): CharacterFactory {
        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equiped'      => false,
        ]);

        return $this;
    }

    /**
     * Creates a location for the player.
     * 
     * @param int $x | 16
     * @param int $y | 16
     * @return CharacterFactory
     */
    public function givePlayerLocation(int $x = 16, int $y = 16): CharacterFactory {

        $this->createMap([
            'character_id'         => $this->character->id,
            'position_x'           => $x,
            'position_y'           => $y,
            'character_position_x' => $x,
            'character_position_y' => $y,
            'game_map_id'          => $this->createGameMap([
                'name'    => 'Sample',
                'path'    => 'path',
                'default' => true,
            ])->id,
        ]);
        
        return $this;
    }

    /**
     * Level up a character x amount of levels.
     * 
     * Handles leveling the character up.
     * 
     * @param int $levels | 1
     * @return CharacterFactory
     */
    public function levelCharacterUp(int $levels = 1): CharacterFactory {
        $characterService = new CharacterService();

        for ($i = 0; $i <= $levels; $i++) {
            $characterService->levelUpCharacter($this->character);

            $this->character->refresh();
        }

        return $this;
    }

    /**
     * Create an adventure log based on an adventure.
     * 
     * You can pass in additional options for the log
     * to be created.
     * 
     * @param Adventure $adventure
     * @param array $options
     */
    public function createAdventureLog(Adventure $adventure, array $options = []): CharacterFactory {

        $log = array_merge([
            'in_progress'  => true,
            'character_id' => $this->character->id,
            'adventure_id' => $adventure->id,
        ], $options);

        $this->character->adventureLogs()->create($log);

        return $this;
    }

    /**
     * Get the character
     * 
     * @return Character
     */
    public function getCharacter(): Character {
        return $this->character->refresh();
    }

    protected function createInventory() {
        $this->character->inventory()->create([
            'character_id' => $this->character->id,
        ]);
    }

    protected function assignBaseSkills() {
        $accuracy = $this->createGameSkill(['name' => 'Accuracy']);
        $dodge    = $this->createGameSkill(['name' => 'Dodge']);
        $looting  = $this->createGameSkill(['name' => 'Looting']);
        
        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $accuracy->id,
        ]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $dodge->id,
        ]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $looting->id,
        ]);
    }

}