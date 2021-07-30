<?php

namespace App\Flare\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Values\CharacterClassValue;

trait ClassBasedBonuses {

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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isProphet()) {
            $class = $character->class;

            $healingSpellsEquipped = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'spell-healing' && $slot->equipped;
            })->isNotEmpty();

            if ($healingSpellsEquipped) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'base_healing_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Get the prophet damage bonus.
     *
     * The damage bonus will only apply if the prophet has a healing spell equipped.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function prophetDamageBonus(Character $character): float {
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isProphet()) {
            $class = $character->class;

            $healingSpellsEquipped = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'spell-healing' && $slot->equipped;
            })->isNotEmpty();

            if ($healingSpellsEquipped) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'skill_bonus');
            }
        }

        return $classBonus;
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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isHeretic()) {
            $class = $character->class;

            $damageSpellsEquipped = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'spell-damage' && $slot->equipped;
            })->isNotEmpty();

            if ($damageSpellsEquipped) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'skill_bonus');
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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isFighter()) {
            $class = $character->class;

            $weaponEquipped = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'weapon' && $slot->equipped;
            })->isNotEmpty();

            if ($weaponEquipped) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'base_damage_mod');
            }
        }

        return $classBonus;
    }

    /**
     * Gets the fighters defence bonus.
     *
     * Only applied if the character has a shield equipped.
     *
     * @param Character $character
     * @return float
     * @throws \Exception
     */
    public function getFightersDefence(Character $character) {
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isFighter()) {
            $class = $character->class;

            $shieldEquipped = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'shield' && $slot->equipped;
            })->isNotEmpty();

            if ($shieldEquipped) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'base_ac_mod');
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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isThief()) {
            $class = $character->class;

            $dualWielding = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'weapon' && $slot->equipped;
            })->count();

            if ($dualWielding === 2) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'base_damage_mod');
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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isThief()) {
            $class = $character->class;

            $dualWielding = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'weapon' && $slot->equipped;
            })->count();

            if ($dualWielding === 2) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'fight_time_out_mod');
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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isThief()) {
            $class = $character->class;

            $hasBow = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'bow' && $slot->equipped;
            })->isNotEmpty();

            if ($hasBow) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'base_damage_mod');
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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isThief()) {
            $class = $character->class;

            $hasBow = $character->inventory->slots->filter(function($slot) {
                return $slot->item->type === 'bow' && $slot->equipped;
            })->isNotEmpty();

            if ($hasBow) {
                $classBonus = $this->getClassBonus($class, $classBonus, 'fight_time_out_mod');
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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isVampire()) {
            $class = $character->class;

            $classBonus = $this->getClassBonus($class, $classBonus, 'base_damage_mod');
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
        $classType  = new CharacterClassValue($character->class->name);
        $classBonus = 0.0;

        if ($classType->isVampire()) {
            $class = $character->class;

            $classBonus = $this->getClassBonus($class, $classBonus, 'base_healing_mod');
        }

        return $classBonus;
    }

    /**
     * Get the class bonus from all associated class related spells.
     *
     * @param GameClass $class
     * @param float $classBonus
     * @param string $type
     * @return float
     */
    private function getClassBonus(GameClass $class, float $classBonus, string $type): float {
        $classSkillIds = $class->gameSkills()->pluck('id')->toArray();
        $skills        = $this->character->skills()->whereIn('game_skill_id', $classSkillIds)->get();

        foreach ($skills as $skill) {
            $classBonus += $skill->{$type};
        }

        return $classBonus;
    }

}
