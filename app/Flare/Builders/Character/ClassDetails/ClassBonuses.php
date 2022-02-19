<?php

namespace App\Flare\Builders\Character\ClassDetails;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Builders\Character\Traits\FetchEquipped;

class ClassBonuses {

    use FetchEquipped;

    /**
     * Get prophet healing bonus from class specific skills.
     *
     * if the character has healing spells equipped, we will then get all their class
     * specific skill bon
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function prophetHealingBonus(Character $character): float {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isProphet()) {
            if ($this->prophetHasHealingSpells($character)) {
                $classBonus = $this->getClassBonus($character, $class, 'base_healing_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Does the prophet have healing spells equipped?
     *
     * @param Character $character
     * @return bool
     */
    public function prophetHasHealingSpells(Character $character): bool {
        $slots = $this->fetchEquipped($character);

        if (is_null($slots)) {
            return false;
        }

        return $slots->filter(function($slot) {
            return $slot->item->type === 'spell-healing';
        })->isNotEmpty();
    }

    /**
     * Does the ranger have any healing spells?
     *
     * @param Character $character
     * @return bool
     */
    public function rangerHasHealingSpells(Character $character): bool {
        return $this->prophetHasHealingSpells($character);
    }

    /**
     * Does the prophet have damage spells equipped?
     *
     * @param Character $character
     * @return bool
     */
    public function prophetHasDamageSpells(Character $character): bool {
        $slots = $this->fetchEquipped($character);

        if (is_null($slots)) {
            return false;
        }

        return $slots->filter(function($slot) {
            return $slot->item->type === 'spell-damage';
        })->isNotEmpty();
    }

    /**
     * Does the prophet get a damage Bonus?
     *
     * @param Character $character
     * @return bool
     */
    public function prophetHasDamageBonus(Character $character): bool {
        $slots = $this->fetchEquipped($character);

        if (is_null($slots)) {
            return false;
        }

        return $slots->filter(function($slot) {
            return $slot->item->type === 'weapon' && $slot->item->type === 'shield';
        })->count() === 2;
    }

    /**
     * Get the heretic spell damage.
     *
     * The bonus will only be returned if the character has a damage spell equipped.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function hereticSpellDamageBonus(Character $character): float {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isHeretic()) {

            $slots = $this->fetchEquipped($character);

            if (is_null($slots)) {
                return $classBonus;
            }

            $damageSpellsEquipped = $slots->filter(function($slot) {
                return $slot->item->type === 'spell-damage';
            })->isNotEmpty();

            if ($damageSpellsEquipped) {
                $classBonus = $this->getClassBonus($character, $class, 'base_damage_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Fetches the Fighters damage bonus.
     *
     * Only applied if they have a weapon equipped.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getFightersDamageBonus(Character $character) {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isFighter()) {

            $slots = $this->fetchEquipped($character);

            if (is_null($slots)) {
                return $classBonus;
            }

            $weaponEquipped = $slots->filter(function($slot) {
                return $slot->item->type === 'weapon';
            })->isNotEmpty();

            if ($weaponEquipped) {
                $classBonus = $this->getClassBonus($character, $class, 'base_damage_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Gets the fighter's defence bonus.
     *
     * Only applied if the character has a shield equipped.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getFightersDefence(Character $character) {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isFighter()) {

            $slots = $this->fetchEquipped($character);

            if (is_null($slots)) {
                return $classBonus;
            }

            $shieldEquipped = $slots->filter(function($slot) {
                return $slot->item->type === 'shield';
            })->isNotEmpty();

            if ($shieldEquipped) {
                $classBonus = $this->getClassBonus($character, $class, 'base_ac_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Get thieves attack bonus.
     *
     * Only applies if they are duel wielding weapons.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getThievesDamageBonus(Character $character) {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isThief()) {

            $slots = $this->fetchEquipped($character);

            if (is_null($slots)) {
                return $classBonus;
            }

            $dualWielding = $slots->filter(function($slot) {
                return $slot->item->type === 'weapon';
            })->count();

            if ($dualWielding === 2) {
                $classBonus = $this->getClassBonus($character, $class, 'base_damage_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Get thieves fight timeout mod.
     *
     * Only applies if they are duel wielding.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getThievesFightTimeout(Character $character) {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isThief()) {

            $slots = $this->fetchEquipped($character);

            if (is_null($slots)) {
                return $classBonus;
            }

            $dualWielding = $slots->filter(function($slot) {
                return $slot->item->type === 'weapon';
            })->count();

            if ($dualWielding === 2) {
                $classBonus = $this->getClassBonus($character, $class, 'fight_time_out_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Get rangers attack bonus.
     *
     * Only applies if they have a bow equipped.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getRangersDamageBonus(Character $character) {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isRanger()) {

            $slots = $this->fetchEquipped($character);

            if (is_null($slots)) {
                return $classBonus;
            }

            $hasBow = $slots->filter(function($slot) {
                return $slot->item->type === 'bow';
            })->isNotEmpty();

            if ($hasBow) {
                $classBonus = $this->getClassBonus($character, $class, 'base_damage_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Get rangers fight timeout mod.
     *
     * Only applies if they have a bow equipped.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getRangersFightTimeout(Character $character) {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isRanger()) {

            $slots = $this->fetchEquipped($character);

            if (is_null($slots)) {
                return $classBonus;
            }

            $hasBow = $slots->filter(function($slot) {
                return $slot->item->type === 'bow';
            })->isNotEmpty();

            if ($hasBow) {
                $classBonus = $this->getClassBonus($character, $class, 'fight_time_out_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Gets the vampires damage bonus.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getVampiresDamageBonus(Character $character) {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isVampire()) {
            $classBonus = $this->getClassBonus($character, $class, 'base_damage_mod');
        }

        return $classBonus;
    }

    /**
     * Gets the vampires healing bonus.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getVampiresHealingBonus(Character $character) {
        $class      = GameClass::find($character->game_class_id);
        $classType  = new CharacterClassValue($class->name);
        $classBonus = 0.0;

        if ($classType->isVampire()) {
            $classBonus = $this->getClassBonus($character, $class, 'base_healing_mod');
        }

        return $classBonus;
    }

    /**
     * Get the class bonus from all associated class related spells.
     *
     * @param Character $character
     * @param GameClass $class
     * @param string $type
     * @return float
     */
    private function getClassBonus(Character $character, GameClass $class, string $type): float {

        $classSkillIds = GameSkill::where('game_class_id', $class->id)->pluck('id')->toArray();
        $skills        = Skill::where('character_id', $character->id)->whereIn('game_skill_id', $classSkillIds)->get();

        $classBonuses = [];

        foreach ($skills as $skill) {
            $classBonuses[] = $skill->{$type};
        }

        return empty($classBonuses) ? 0.0 : max($classBonuses);
    }
}
