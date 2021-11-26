<?php

namespace App\Flare\Builders;

use App\Flare\Models\PassiveSkill;
use App\Flare\Models\User;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Values\BaseStatValue;
use App\Flare\Values\BaseSkillValue;
use App\Game\Core\Values\FactionLevel;

class CharacterBuilder {

    /**
     * @var GameRace $race
     */
    private $race;

    /**
     * @var GameClass $class
     */
    private $class;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * Set the chosen race
     *
     * @param GameRace $race
     * @return CharacterBuilder
     */
    public function setRace(GameRace $race): CharacterBuilder {
        $this->race = $race;

        return $this;
    }

    /**
     * Set the chosen class
     *
     * @param GameClass $class
     * @return CharacterBuilder
     */
    public function setClass(GameClass $class): CharacterBuilder {
        $this->class = $class;

        return $this;
    }

    /**
     * Create the character.
     *
     * This includes the inventory, a basic weapon that is then equipped
     * as well as your map position.
     *
     * We also set the characters base stats based on any racial and class modifications.
     *
     * @param User $user
     * @param GameMap $map
     * @param string $name
     * @return CharacterBuilder
     */
    public function createCharacter(User $user, GameMap $map, string $name): CharacterBuilder {
        $baseStat = resolve(BaseStatValue::class)->setRace($this->race)->setClass($this->class);

        $this->character = Character::create([
            'user_id'       => $user->id,
            'game_race_id'  => $this->race->id,
            'game_class_id' => $this->class->id,
            'name'          => $name,
            'damage_stat'   => $this->class->damage_stat,
            'xp'            => 0,
            'xp_next'       => 100,
            'str'           => $baseStat->str(),
            'dur'           => $baseStat->dur(),
            'dex'           => $baseStat->dex(),
            'chr'           => $baseStat->chr(),
            'int'           => $baseStat->int(),
            'agi'           => $baseStat->agi(),
            'focus'         => $baseStat->focus(),
            'ac'            => $baseStat->ac(),
            'gold'          => 1000,
        ]);

        $this->character->inventory()->create([
            'character_id' => $this->character->id
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => Item::first()->id,
            'equipped'     => true,
            'position'     => 'left-hand',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->character->inventorySets()->create([
                'character_id'    => $this->character->id,
                'can_be_equipped' => true,
            ]);
        }

        $this->character->map()->create([
            'character_id' => $this->character->id,
            'game_map_id'  => $map->id,
        ]);

        $this->assignFactions();

        return $this;
    }

    /**
     * Creates a test character with out a user.
     *
     * @param GameMap $map
     * @param string $name
     * @return $this
     */
    public function createTestCharacter(GameMap $map, string $name): CharacterBuilder {
        $baseStat = resolve(BaseStatValue::class)->setRace($this->race)->setClass($this->class);

        $this->character = Character::create([
            'game_race_id'  => $this->race->id,
            'game_class_id' => $this->class->id,
            'name'          => $name,
            'damage_stat'   => $this->class->damage_stat,
            'xp'            => 0,
            'xp_next'       => 100,
            'str'           => $baseStat->str(),
            'dur'           => $baseStat->dur(),
            'dex'           => $baseStat->dex(),
            'chr'           => $baseStat->chr(),
            'int'           => $baseStat->int(),
            'agi'           => $baseStat->agi(),
            'focus'         => $baseStat->focus(),
            'ac'            => $baseStat->ac(),
        ]);

        $this->character->inventory()->create([
            'character_id' => $this->character->id
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => Item::first()->id,
            'equipped'     => true,
            'position'     => 'left-hand',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->character->inventorySets()->create([
                'character_id'    => $this->character->id,
                'can_be_equipped' => true,
            ]);
        }

        $this->character->map()->create([
            'character_id' => $this->character->id,
            'game_map_id'  => $map->id,
        ]);

        return $this;
    }

    /**
     * Assign skills to the user.
     *
     * This assigns all skills in the database.
     *
     * @return CharacterBuilder
     */
    public function assignSkills(): CharacterBuilder {
        foreach (GameSkill::whereNull('game_class_id')->get() as $skill) {
            $this->character->skills()->create(
                resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($this->character, $skill)
            );
        }

        /**
         * Assign the skills assigned to this character's class.
         */
        foreach ($this->character->class->gameSkills as $skill) {
            $this->character->skills()->create(
                resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($this->character, $skill)
            );
        }

        return $this;
    }

    /**
     * Assign passive skills to the player.
     *
     * @return $this
     */
    public function assignPassiveSkills(): CharacterBuilder {
        foreach (PassiveSkill::all() as $passiveSkill) {
            $this->character->passiveSkills()->create([
                'character_id'     => $this->character->id,
                'passive_skill_id' => $passiveSkill->id,
                'current_level'    => 0,
                'hours_to_next'    => $passiveSkill->hours_per_level,
                'is_locked'        => $passiveSkill->is_locked,
            ]);
        }

        return $this;
    }

    /**
     * Assign the factions to the character, one for each map.
     *
     * @return CharacterBuilder
     */
    public function assignFactions(): CharacterBuilder {
        $gameMaps = GameMap::all();

        foreach ($gameMaps as $gameMap) {
            $this->character->factions()->create([
                'character_id'  => $this->character->id,
                'game_map_id'   => $gameMap->id,
                'points_needed' => FactionLevel::getPointsNeeded(0),
            ]);
        }

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Get the character object
     *
     * @return Character
     */
    public function character(): Character {
        return $this->character->refresh();
    }
}
