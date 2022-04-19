<?php

namespace Tests\Setup\Character;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameClass;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\Quest;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Game\Core\Values\FactionLevel;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Facades\Cache;
use Str;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\GameMap;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\User;
use App\Game\Core\Services\CharacterService;
use Tests\Setup\AttackDataCacheSetUp;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMap;
use Tests\Traits\CreateMarketBoardListing;
use Tests\Traits\CreatePassiveSkill;
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
        CreateSkill,
        CreateMarketBoardListing,
        CreatePassiveSkill;

    private Character $character;

    private $inventorySetManagement;

    /**
     * Creates a base character associated with a user.
     *
     * This base character will also have:
     *
     * - Core inventory - empty.
     * - Base Skills: Accuracy, Looting and Dodge.
     *
     * @param array $raceOptions
     * @param array|GameClass $classOptions
     * @return CharacterFactory
     */
    public function createBaseCharacter(array $raceOptions = [], array|GameClass $classOptions = []): CharacterFactory {
        $race  = $this->createRace($raceOptions);

        if ($classOptions instanceof GameClass) {
            $class = $classOptions;
        } else {
            $class = $this->createClass($classOptions);
        }

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

        $this->assignPassiveSkills();

        $character = $this->character->refresh();

        Cache::put('character-attack-data-' . $character->id, (new AttackDataCacheSetUp())->getCacheObject());

        return $this;
    }

    public function assignPassiveSkills(GameBuilding $gameBuilding = null): CharacterFactory {

        $this->createPassiveForCharacter(PassiveSkillTypeValue::KINGDOM_DEFENCE);
        $this->createPassiveForCharacter(PassiveSkillTypeValue::KINGDOM_RESOURCE_GAIN);
        $this->createPassiveForCharacter(PassiveSkillTypeValue::KINGDOM_UNIT_COST_REDUCTION);
        $this->createPassiveForCharacter(PassiveSkillTypeValue::KINGDOM_BUILDING_COST_REDUCTION);
        $this->createPassiveForCharacter(PassiveSkillTypeValue::IRON_COST_REDUCTION);
        $this->createPassiveForCharacter(PassiveSkillTypeValue::POPULATION_COST_REDUCTION);

        $this->character = $this->character->refresh();

        $this->createPassiveForCharacter(PassiveSkillTypeValue::UNLOCKS_BUILDING, [
            'name'                     => is_null($gameBuilding) ? 'Sample Passive Skill 101' : $gameBuilding->name,
            'is_locked'                => true,
            'unlocks_at_level'         => 1,
            'parent_skill_id'          => $this->character->passiveSkills()->first()->passiveSkill->id,
        ]);

        return $this;
    }

    public function completeQuest(Quest $quest): CharacterFactory {
        $this->character->questsCompleted()->create([
            'quest_id'     => $quest->id,
            'character_id' => $this->character->id,
        ]);

        $this->character = $this->character->refresh();

        return $this;
    }

    public function createPassiveForCharacter(int $type, array $options = []): CharacterFactory {

        $isLocked     = false;
        $parentId     = null;
        $currentLevel = 0;

        if (isset($options['is_locked'])) {
            $isLocked = $options['is_locked'];
        }

        if (isset($options['parent_skill_id'])) {
            $parentId = $this->character->passiveSkills()->where('passive_skill_id', $options['parent_skill_id'])->first()->id;
        }

        if (isset($options['current_level'])) {
            $currentLevel = $options['current_level'];

            unset($options['current_level']);
        }

        $this->character->passiveSkills()->create([
            'character_id'      => $this->character->id,
            'passive_skill_id'  => $this->createPassiveSkill(array_merge([
                'effect_type' => $type,
            ], $options))->id,
            'parent_skill_id'   => $parentId,
            'current_level'     => $currentLevel,
            'hours_to_next'     => 1,
            'started_at'        => null,
            'completed_at'      => null,
            'is_locked'         => $isLocked,
        ]);

        return $this;
    }

    public function assignAutomation(array $options): CharacterFactory {
        $this->character->currentAutomations()->create(array_merge([
            'character_id'                   => $this->character->id,
            'monster_id'                     => null,
            'type'                           => AutomationType::EXPLORING,
            'started_at'                     => now(),
            'completed_at'                   => now()->addHours(25),
            'attack_type'                    => AttackTypeValue::CAST,
        ], $options));

        return $this;
    }

    /**
     * Assign Faction system.
     *
     * @return CharacterFactory
     */
    public function assignFactionSystem(): CharacterFactory {

        // In case it's called again, we don't want duplicates.
        // If the monster updates its plane or a monster is created
        // with a different plane than the character, we need to make sure their
        // faction system is always up-to-date.
        $this->character->factions()->delete();

        $gameMaps = GameMap::all();

        foreach ($gameMaps as $map) {
            $this->character->factions()->create([
                'current_level' => 1,
                'character_id'  => $this->character->id,
                'game_map_id'   => $map->id,
                'points_needed' => FactionLevel::getPointsNeeded(0),
            ]);
        }

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
     * Fetch the kingdom management class.
     *
     * @return KingdomManagement.
     */
    public function kingdomManagement(): KingdomManagement {
        return new KingdomManagement($this->character, $this);
    }

    /**
     * Fetches inventory management.
     *
     * Use existing instantiation if it exists.
     *
     * @return InventorySetManagement
     */
    public function inventorySetManagement(): InventorySetManagement {

        if (is_null($this->inventorySetManagement)) {
            $this->inventorySetManagement = new InventorySetManagement($this->character, $this);
        }

        return $this->inventorySetManagement;
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
     * Lets you update the character's user profile.
     *
     * @param array $changes
     * @return $this
     */
    public function updateUser(array $changes = []): CharacterFactory {
        $this->character->user()->update($changes);

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Lets you ban a character
     *
     * If the length is not set, then the ban is forever.
     *
     * Length should be a carbon date object.
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

        resolve(BuildCharacterAttackTypes::class)->buildCache($this->character->refresh());

        return $this;
    }

    public function equipStrongGear() {
        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '1000000'
        ]);

        $itemTwo = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '1000000'
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equipped'     => true,
            'position'     => 'right-hand'
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $itemTwo->id,
            'equipped'     => true,
            'position'     => 'left-hand'
        ]);

        resolve(BuildCharacterAttackTypes::class)->buildCache($this->character->refresh());

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
        $gameMap = GameMap::where('default', true)->get();
        $id      = 0;

        if ($gameMap->isNotEmpty()) {
            $id = $gameMap->first()->id;
        } else {
            $id = $this->createGameMap([
                'name'    => 'Surface',
                'path'    => 'path',
                'default' => true,
            ])->id;
        }
        $this->createMap([
            'character_id'         => $this->character->id,
            'position_x'           => $x,
            'position_y'           => $y,
            'character_position_x' => $x,
            'character_position_y' => $y,
            'game_map_id'          => $id,
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

            $this->character = $this->character->refresh();
        }

        resolve(BuildCharacterAttackTypes::class)->buildCache($this->character->refresh());

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
        $skill = $this->character->skills->where('name', $name)->first();

        if (is_null($skill)) {
            throw new \Exception($name . ' not found.');
        }

        $skill->update($changes);

        $this->character = $this->character->refresh();

        resolve(BuildCharacterAttackTypes::class)->buildCache($this->character->refresh());

        return $this;
    }

    /**
     * Assign a new skill to a character.
     *
     * @param GameSkill $skill
     * @param int $level | 1
     * @param bool $locked
     * @param array $options
     * @return characterFactory
     */
    public function assignSkill(GameSkill $skill, int $level = 1, bool $locked = false, array $options = []): CharacterFactory {
        $this->character->skills()->create(array_merge([
            'game_skill_id' => $skill->id,
            'character_id'  => $this->character->id,
            'level'         => $level,
            'xp'            => 0,
            'xp_max'        => 100,
            'is_locked'     => $locked,
        ], $options));

        resolve(BuildCharacterAttackTypes::class)->buildCache($this->character->refresh());

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

        $character = $this->character->refresh();

        return $character;
    }

    /**
     * Builds the character cache data.
     *
     * @return $this
     */
    public function buildCharacterCacheData(): CharacterFactory {
        $character = $this->character->Refresh();

        if (!Cache::has('character-attack-data-' . $character->id)) {
            resolve(BuildCharacterAttackTypes::class)->buildCache($character);
        }

        return $this;
    }

    /**
     * Creates a market board listing.
     *
     * @param Item|null $item
     * @return $this
     */
    public function createMarketListing(?Item $item = null): CharacterFactory {
        if (is_null($item)) {
            $item = $this->createItem();
        }

        $this->createMarketBoardListing([
            'character_id' => $this->character->id,
            'item_id'      => $item->id,
            'listed_price' => 10000,
        ]);

        return $this;
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
     * Create the core inventory.
     */
    protected function createInventory() {
        $this->character->inventory()->create([
            'character_id' => $this->character->id,
        ]);
    }

    /**
     * Assign Base Skills
     */
    protected function assignBaseSkills() {
        $accuracy        = $this->createGameSkill(['name' => 'Accuracy']);
        $timeout         = $this->createGameSkill(['name' => 'Fighters Timeout', 'type' => SkillTypeValue::EFFECTS_BATTLE_TIMER]);
        $castingAccuracy = $this->createGameSkill(['name' => 'Casting Accuracy']);
        $criticality     = $this->createGameSkill(['name' => 'Criticality']);
        $dodge           = $this->createGameSkill(['name' => 'Dodge']);
        $looting         = $this->createGameSkill(['name' => 'Looting']);
        $kingmanship     = $this->createGameSkill(['name' => 'Kingmanship', 'type' => SkillTypeValue::EFFECTS_KINGDOM]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $accuracy->id,
        ]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $timeout->id,
        ]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $dodge->id,
        ]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $looting->id,
        ]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $castingAccuracy->id,
        ]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $criticality->id,
        ]);

        $this->createSkill([
            'character_id'  => $this->character->id,
            'game_skill_id' => $kingmanship->id,
        ]);
    }

}
