<?php

namespace App\Game\Character\CharacterCreation\Services;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\User;
use App\Flare\Values\BaseSkillValue;
use App\Flare\Values\BaseStatValue;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Values\FactionLevel;
use Exception;

class CharacterBuilderService
{
    private GameRace $race;

    private GameClass $class;

    private Character $character;

    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    public function __construct(BuildCharacterAttackTypes $buildCharacterAttackTypes)
    {
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
    }

    /**
     * Set the chosen race
     */
    public function setRace(GameRace $race): CharacterBuilderService
    {
        $this->race = $race;

        return $this;
    }

    /**
     * Set the chosen class
     */
    public function setClass(GameClass $class): CharacterBuilderService
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Set the character for assigning new skills.
     *
     * @return $this
     */
    public function setCharacter(Character $character): CharacterBuilderService
    {
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
     * @throws Exception
     */
    public function createCharacter(User $user, GameMap $map, string $name): CharacterBuilderService
    {
        $baseStat = resolve(BaseStatValue::class)->setRace($this->race)->setClass($this->class);

        $this->character = Character::create([
            'user_id' => $user->id,
            'game_race_id' => $this->race->id,
            'game_class_id' => $this->class->id,
            'name' => $name,
            'damage_stat' => $this->class->damage_stat,
            'xp' => 0,
            'xp_next' => 100,
            'str' => $baseStat->str(),
            'dur' => $baseStat->dur(),
            'dex' => $baseStat->dex(),
            'chr' => $baseStat->chr(),
            'int' => $baseStat->int(),
            'agi' => $baseStat->agi(),
            'focus' => $baseStat->focus(),
            'ac' => $baseStat->ac(),
            'gold' => 1000,
        ]);

        $this->character->gemBag()->create(['character_id' => $this->character->id]);

        $this->character->inventory()->create([
            'character_id' => $this->character->id,
        ]);

        $types = ItemTypeMapping::getForClass($this->character->class->name);


        if (is_array($types)) {
            $weaponType = $types[0];
        } else {
            $weaponType = $types;
        }

        $starterWeaponId = Item::where('type', $weaponType)
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('specialty_type')
            ->doesntHave('appliedHolyStacks')
            ->doesntHave('sockets')
            ->where('skill_level_required', 1)
            ->first()->id;

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $starterWeaponId,
            'equipped' => true,
            'position' => 'left-hand',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->character->inventorySets()->create([
                'character_id' => $this->character->id,
                'can_be_equipped' => true,
            ]);
        }

        $this->character->map()->create([
            'character_id' => $this->character->id,
            'game_map_id' => $map->id,
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
     */
    public function assignSkills(): CharacterBuilderService
    {
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
    public function assignPassiveSkills(): CharacterBuilderService
    {
        foreach (PassiveSkill::all() as $passiveSkill) {
            $characterPassive = $this->character->passiveSkills()->where('passive_skill_id', $passiveSkill->id)->first();

            $parentId = $passiveSkill->parent_skill_id;
            $parent = null;

            if (! is_null($parentId)) {
                $parent = $this->character->passiveSkills()->where('passive_skill_id', $parentId)->first();
            }

            if (is_null($characterPassive)) {
                $this->character->passiveSkills()->create([
                    'character_id' => $this->character->id,
                    'passive_skill_id' => $passiveSkill->id,
                    'current_level' => 0,
                    'hours_to_next' => $passiveSkill->hours_per_level,
                    'is_locked' => $this->getIsSkillLocked($passiveSkill, $parent),
                    'parent_skill_id' => ! is_null($parent) ? $parent->id : null,
                ]);
            } else {
                $characterPassive->update([
                    'is_locked' => $this->getIsSkillLocked($characterPassive->passiveSkill, $parent),
                ]);
            }
        }

        $this->character = $this->character->refresh();

        return $this;
    }

    protected function getIsSkillLocked(PassiveSkill $passiveSkill, ?CharacterPassiveSkill $parentSkill = null): bool
    {

        $isLocked = $passiveSkill->is_locked;

        if (! is_null($parentSkill)) {
            $isLocked = $passiveSkill->unlocks_at_level > $parentSkill->current_level;
        }

        $foundQuest = Quest::where('unlocks_passive_id', $passiveSkill->id)->first();

        if (! is_null($foundQuest)) {
            $isLocked = is_null($this->character->questsCompleted->where('quest_id', $foundQuest->id)->first());
        }

        return $isLocked;
    }

    /**
     * Assign the factions to the character, one for each map.
     */
    public function assignFactions(): CharacterBuilderService
    {
        $gameMaps = GameMap::all();

        foreach ($gameMaps as $gameMap) {

            if (! $gameMap->mapType()->isPurgatory()) {
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
     * @throws Exception
     */
    public function assignClassRanks(): CharacterBuilderService
    {
        $gameClasses = GameClass::all();

        foreach ($gameClasses as $gameClass) {
            $classRank = $this->character->classRanks()->create([
                'character_id' => $this->character->id,
                'game_class_id' => $gameClass->id,
                'current_xp' => 0,
                'required_xp' => ClassRankValue::XP_PER_LEVEL,
                'level' => 0,
            ]);

            $this->assignWeaponMasteriesToClassRanks($classRank);
        }

        $this->character = $this->character->refresh();

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    public function buildCharacterCache(): CharacterBuilderService
    {
        $this->buildCharacterAttackTypes->buildCache($this->character);

        return $this;
    }

    /**
     * Get the character object
     */
    public function character(): Character
    {
        return $this->character;
    }

    /**
     * Assigns weapon masteries to class ranks.
     *
     * @throws Exception
     */
    protected function assignWeaponMasteriesToClassRanks(CharacterClassRank $classRank): void
    {
        foreach (ItemType::allWeaponTypes() as $type) {
            $classRank->weaponMasteries()->create([
                'character_class_rank_id' => $classRank->id,
                'weapon_type' => $type,
                'current_xp' => 0,
                'required_xp' => WeaponMasteryValue::XP_PER_LEVEL,
                'level' => $this->getDefaultLevel($classRank, $type),
            ]);
        }
    }

    /**
     * Get default level for weapon mastery.
     *
     * @return int
     *
     * @throws Exception
     */
    protected function getDefaultLevel(CharacterClassRank $classRank, string $type)
    {
        $mapping = ItemTypeMapping::getForClass(
            $classRank->gameClass->name
        );

        if (is_null($mapping)) {
            return 0;
        }

        if (is_string($mapping)) {
            return $type === $mapping
                ? 5
                : 0;
        }

        $pos = array_search(
            $type,
            $mapping,
            true
        );

        if ($pos === false) {
            return 0;
        }

        $classType = $classRank->gameClass->type();

        if ($classType->isPrisoner()) {
            return $pos === 0
                ? 5
                : 0;
        }

        if ($classType->isMerchant()) {
            return $pos === 0
                ? 2
                : 3;
        }

        return 5;
    }

}
