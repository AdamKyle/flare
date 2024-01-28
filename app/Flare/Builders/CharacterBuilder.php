<?php

namespace App\Flare\Builders;

use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\User;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Values\BaseStatValue;
use App\Flare\Values\BaseSkillValue;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Values\FactionLevel;
use Exception;

class CharacterBuilder {

    /**
     * @var GameRace $race
     */
    private GameRace $race;

    /**
     * @var GameClass $class
     */
    private GameClass $class;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    public function __construct(BuildCharacterAttackTypes $buildCharacterAttackTypes) {
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
    }

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
     * Set the character for assigning new skills.
     *
     * @param Character $character
     * @return $this
     */
    public function setCharacter(Character $character): CharacterBuilder {
        $this->character = $character;

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

        $this->character->gemBag()->create(['character_id' => $this->character->id]);

        $this->character->inventory()->create([
            'character_id' => $this->character->id
        ]);

        $starterWeaponId = Item::where('type', 'weapon')->whereNull('item_suffix_id')->whereNull('item_prefix_id')->orderBy('cost', 'asc')->first()->id;

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $starterWeaponId,
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

        $this->assignClassRanks();

        $this->character = $this->character->refresh();

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

            $existingSkill = $this->character->skills()->where('game_skill_id', $skill->id)->first();

            if (is_null($existingSkill)) {
                $this->character->skills()->create(
                    resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($this->character, $skill)
                );
            }
        }

        /**
         * Assign the skills assigned to this character's class.
         */
        foreach ($this->character->class->gameSkills as $skill) {
            $existingSkill = $this->character->skills()->where('game_skill_id', $skill->id)->first();

            if (is_null($existingSkill)) {
                $this->character->skills()->create(
                    resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($this->character, $skill)
                );
            }
        }

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Assign passive skills to the player.
     *
     * @return $this
     */
    public function assignPassiveSkills(): CharacterBuilder {
        foreach (PassiveSkill::all() as $passiveSkill) {
            $characterPassive = $this->character->passiveSkills()->where('passive_skill_id', $passiveSkill->id)->first();

            if (is_null($characterPassive)) {
                $parentId = $passiveSkill->parent_skill_id;
                $parent   = null;

                if (!is_null($parentId)) {
                    $parent = $this->character->passiveSkills()->where('passive_skill_id', $parentId)->first();
                }

                $isLocked = $passiveSkill->is_locked;

                if (!is_null($parent)) {
                    $isLocked = $passiveSkill->unlocks_at_level >= $parent->current_level;
                }

                $this->character->passiveSkills()->create([
                    'character_id'     => $this->character->id,
                    'passive_skill_id' => $passiveSkill->id,
                    'current_level'    => 0,
                    'hours_to_next'    => $passiveSkill->hours_per_level,
                    'is_locked'        => $isLocked,
                    'parent_skill_id'  => !is_null($parent) ? $parent->id : null,
                ]);
            }
        }

        $this->character = $this->character->refresh();

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

            if (!$gameMap->mapType()->isPurgatory()) {
                $this->character->factions()->create([
                    'character_id' => $this->character->id,
                    'game_map_id' => $gameMap->id,
                    'points_needed' => FactionLevel::getPointsNeeded(0),
                ]);
            }
        }

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * Assign Class Ranks to a character.
     *
     * @return CharacterBuilder
     */
    public function assignClassRanks(): CharacterBuilder {
        $gameClasses = GameClass::all();

        foreach ($gameClasses as $gameClass) {
            $classRank = $this->character->classRanks()->create([
                'character_id'   => $this->character->id,
                'game_class_id'  => $gameClass->id,
                'current_xp'     => 0,
                'required_xp'    => ClassRankValue::XP_PER_LEVEL,
                'level'          => 0,
            ]);

            $this->assignWeaponMasteriesToClassRanks($classRank);
        }

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function buildCharacterCache(): CharacterBuilder {
        $this->buildCharacterAttackTypes->buildCache($this->character);

        return $this;
    }

    /**
     * Get the character object
     *
     * @return Character
     */
    public function character(): Character {
        return $this->character;
    }

    /**
     * Assigns weapon masteries to class ranks.
     *
     * @param CharacterClassRank $classRank
     * @return void
     * @throws Exception
     */
    protected function assignWeaponMasteriesToClassRanks(CharacterClassRank $classRank): void {
        foreach (WeaponMasteryValue::getTypes() as $type) {
            $classRank->weaponMasteries()->create([
                'character_class_rank_id'   => $classRank->id,
                'weapon_type'               => $type,
                'current_xp'                => 0,
                'required_xp'               => WeaponMasteryValue::XP_PER_LEVEL,
                'level'                     => $this->getDefaultLevel($classRank, $type),
            ]);
        }
    }

    /**
     * Get default level for weapon mastery.
     *
     * @param CharacterClassRank $classRank
     * @param int $type
     * @return int
     * @throws Exception
     */
    protected function getDefaultLevel(CharacterClassRank $classRank, int $type) {
        if (($classRank->gameClass->type()->isFighter() ||
                $classRank->gameClass->type()->isThief() ||
                $classRank->gameClass->type()->isVampire() ||
                $classRank->gameClass->type()->isBlackSmith()) && (new WeaponMasteryValue($type))->isWeapon())
        {
            return 5;
        }

        if (($classRank->gameClass->type()->isHeretic() || $classRank->gameClass->type()->isArcaneAlchemist()) && (new WeaponMasteryValue($type))->isStaff()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isHeretic() || $classRank->gameClass->type()->isArcaneAlchemist()) && (new WeaponMasteryValue($type))->isDamageSpell()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isProphet()) && (new WeaponMasteryValue($type))->isHealingSpell()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isRanger() || $classRank->gameClass->type()->isArcaneAlchemist()) && (new WeaponMasteryValue($type))->isHealingSpell()) {
            return 2;
        }

        if (($classRank->gameClass->type()->isRanger()) && (new WeaponMasteryValue($type))->isBow()) {
            return 5;
        }

        if (($classRank->gameClass->type()->isThief() || $classRank->gameClass->type()->isArcaneAlchemist()) && (new WeaponMasteryValue($type))->isBow()) {
            return 2;
        }

        if (($classRank->gameClass->type()->isBlackSmith()) && (new WeaponMasteryValue($type))->isHammer()) {
            return 5;
        }

        return 0;
    }
}
