<?php

namespace Tests\Setup\Character;

use Str;
use Hash;
use Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use App\Game\Core\Services\CharacterService;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMap;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateSkill;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateSecurityQuestion;

class CharacterFactory {

    use CreateRace,
        CreateClass,
        CreateCharacter,
        CreateUser,
        CreateItem,
        CreateMap,
        CreateGameMap,
        CreateGameSkill,
        CreateSkill,
        CreateSecurityQuestion;

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

        $this->createSecurityQuestions();

        $this->createInventory();

        $this->assignBaseSkills();

        return $this;
    }

    /**
     * Fetch the inventory management class.
     * 
     * @return InventoryManagement
     */
    public function inventoryManagement(): InventoryManagement {
        return new InventoryManagement($this->character, $this);
    }

    /**
     * Fetch the adventure management class.
     * 
     * @return AdventureManagement
     */
    public function adventureManagement(): AdventureManagement {
        return new AdventureManagement($this->character, $this);
    }

    /**
     * Lets you update the character
     * 
     * @param array $changes | []
     * @return CharacterFactory
     */
    public function updateCharacter(array $changes = []): CharacterFactory {
        $this->character->update($changes);

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Give the character a snapshot.
     * 
     * Make sure to call this before leveling the chracter up in situations where
     * you need to test with character snap shots.
     * 
     * @return CharacterFactory
     */
    public function giveSnapShot(): CharacterFactory {
        $this->character->snapShots()->create([
            'character_id' => $this->character->id,
            'snap_shot'    => $this->character->getAttributes(),
        ]);

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Marks the user as not a test user.
     * 
     * Useful insituations where you do not want a test user, but an "actual" user.
     * 
     * @return CharacterFactory
     */
    public function userIsNotTest(): CharacterFactory {
        $this->character->user->update([
            'is_test' => false
        ]);

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Lets you ban a character
     * 
     * If the length is not set, then the ban is for ever.
     * 
     * Length should be acarbon date object.
     * 
     * @param string $reason | null
     * @param string $request | null
     * @param $forLength | null
     * @return CharacterFactory
     */
    public function banCharacter(string $reason = null, string $request = null, $forLength = null): CharacterFactory {
        $this->character->user->update([
            'is_banned'      => true,
            'unbanned_at'    => $forLength,
            'banned_reason'  => $reason,
            'un_ban_request' => $request,
        ]);

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
            'equipped'     => true,
            'position'     => 'right-hand'
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
     * Allows one to update a characters location.
     * 
     * @param int $x | 16
     * @param int $y | 16
     * @return CharacterFactory
     */
    public function updateLocation(int $x = 16, int $y = 16): CharacterFactory {
        $this->character->map()->update([
            'position_x'           => $x,
            'position_y'           => $y,
            'character_position_x' => $x,
            'character_position_y' => $y,
        ]);

        $this->character = $this->character->refresh();

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
     * Update a specific skill associated with a character.
     * 
     * @param string $name
     * @param array $changes | []
     * @return CharacterFactory
     */
    public function updateSkill(string $name, array $changes = []): CharacterFactory {
        $skill = $this->character->skills->filter(function($skill) use($name) {
            return $skill->name === $name;
        })->first();

        if (is_null($skill)) {
            throw new \Exception($name . ' not found.');
        }

        $skill->update($changes);

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Assign a new skill to a character.
     * 
     * @param GameSkill $skill
     * @return characterFactory
     */
    public function assignSkill(GameSkill $skill): CharacterFactory {
        $this->character->skills()->create([
            'game_skill_id' => $skill->id,
            'character_id'  => $this->character->id,
            'level'         => 1,
            'xp'            => 0,
            'xp_max'        => 100,
        ]);

        return $this;
    }

    /**
     * Train a skill.
     * 
     * Sets a skill to training, assuming no other skill is currently being trained.
     * 
     * @param string $name
     * @return CharacterFactory
     */
    public function trainSkill(string $name): CharacterFactory {
        $skill = $this->character->skills->filter(function($skill) {
            return $skill->currently_training;
        })->first();

        if (!is_null($skill)) {
            throw new \Exception('Already have a skill in training.');
        }

        $this->character->skills->each(function($skill) use($name) {
            if ($skill->name === $name) {
                $skill->update([
                    'currently_training' => true
                ]);
            }
        });

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

    /**
     * Get the user.
     * 
     * @return User
     */
    public function getUser(): User {
        return $this->character->user;
    }

    /**
     * Gets the users security Questions
     * 
     * @return Collection
     */
    public function getSecurityQuestions(): Collection {
        return $this->getUser()->securityQuestions;
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

    protected function createSecurityQuestions() {

        $user = $this->getUser();

        $this->createSecurityQuestion([
            'user_id'  => $user->id,
            'question' => 'test question',
            'answer'   => Hash::make('test'),
        ]);

        $this->createSecurityQuestion([
            'user_id'  => $this->character->user->id,
            'question' => 'test question 2',
            'answer'   => Hash::make('test2'),
        ]);
    }

}