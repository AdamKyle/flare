<?php

namespace App\Game\Character\CharacterCreation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\GameSkill;
use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Skills\Builders\BaseSkillBuilder;
use Exception;

class CharacterBuilderService
{
    private Character $character;

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
                    resolve(BaseSkillBuilder::class)->getBaseCharacterSkillValue($this->character, $skill)
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
                    resolve(BaseSkillBuilder::class)->getBaseCharacterSkillValue($this->character, $skill)
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
     * Assigns weapon masteries to class ranks.
     *
     * @throws Exception
     */
    public function assignWeaponMasteriesToClassRanks(CharacterClassRank $classRank): void
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
