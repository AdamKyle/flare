<?php

namespace App\Flare\Builders\Character\AttackDetails\DamageDetails;

use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Builders\Character\BaseCharacterInfo;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;

class WeaponInformation {

    use FetchEquipped;

    /**
     * @var BaseCharacterInfo $baseCharacterInfo
     */
    private $baseCharacterInfo;

    private $holyStacks;

    public function __construct(HolyStacks $holyStacks) {
        $this->holyStacks = $holyStacks;
    }

    /**
     * @param BaseCharacterInfo $baseCharacterInfo
     * @return WeaponInformation
     */
    public function setCharacterInformation(BaseCharacterInfo $baseCharacterInfo): WeaponInformation {
        $this->baseCharacterInfo = $baseCharacterInfo;

        return $this;
    }

    /**
     *
     * @param Character $character
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function getWeaponDamage(Character $character, bool $voided = false): int {
        $damage = [];

        $slots = $this->fetchEquipped($character);

        if (is_null($slots)) {
            return 0;
        }

        $totalWeaponDamage = $this->fetchTotalWeaponDamage($slots, $voided);

        $damage = $this->damageModifiers($character, $totalWeaponDamage, $voided);

        return $this->calculateWeaponDamage($character, $damage, $voided);
    }

    /**
     * Calculate Damage Modifiers
     *
     * @param Character $character
     * @param int $damage
     * @param bool $voided
     * @return int
     * @throws \Exception
     */
    public function damageModifiers(Character $character, int $damage, bool $voided): int {
        $class = GameClass::find($character->game_class_id);

        if ($class->type()->isFighter()) {
            if ($voided) {
                $statIncrease = $character->str * .15;
            } else {
                $statIncrease = $this->baseCharacterInfo->statMod($character, 'str') * 0.15;
            }

            $damage += $statIncrease;
        }

        if($class->type()->isThief() || $character->classType()->isRanger()) {
            if ($voided) {
                $statIncrease = $character->dex * .05;
            } else {
                $statIncrease = $this->baseCharacterInfo->statMod($character, 'dex') * 0.05;
            }

            $damage += $statIncrease;
        }

        if ($class->type()->isProphet()) {
            if ($this->baseCharacterInfo->getClassBonuses()->prophetHasDamageBonus($character)) {
                if ($voided) {
                    $statIncrease = $character->chr * .10;
                } else {
                    $statIncrease = $this->baseCharacterInfo->statMod($character, 'chr') * 0.10;
                }

                $damage += $statIncrease;
            }
        }

        return ceil($damage);
    }

    /**
     * Calculate Weapon Damage.
     *
     * @param Character $character
     * @param int|float $damage
     * @param bool $voided
     * @return int|float
     */
    public function calculateWeaponDamage(Character $character, int|float $damage, bool $voided = false): int|float {

        if ($damage === 0) {
            $class  = GameClass::find($character->game_class_id);
            $damage = $voided ? $character->{$character->damage_stat} : $this->baseCharacterInfo->statMod($character, $character->damage_stat);

            if ($class->type()->isFighter()) {
                $damage = $damage * 0.05;
            } else {
                $damage = $damage * 0.02;
            }
        }

        $gameSkillIds = GameSkill::where('base_damage_mod_bonus_per_level', '>', 0.0)->pluck('id')->toArray();
        $skills       = Skill::whereIn('game_skill_id', $gameSkillIds)->where('character_id', $character->id)->get();

        foreach ($skills as $skill) {
            $damage += $damage * $skill->base_damage_mod;
        }

        $damage += $damage * $this->holyStacks->fetchAttackBonus($character);

        return ceil($damage);
    }

    /**
     * Fetch the actual weapon damage.
     *
     * @param Collection $slots
     * @param bool $voided
     * @return int
     */
    protected function fetchTotalWeaponDamage(Collection $slots, bool $voided = false): int {
        $damage = 0;

        foreach ($slots as $slot) {
            if ($slot->item->type === 'weapon') {
                if (!$voided) {
                    $damage += $slot->item->getTotalDamage();
                } else {
                    $damage +=  $slot->item->base_damage;
                }
            } else if ($slot->item->type === 'bow') {
                if (!$voided) {
                    $damage += $slot->item->getTotalDamage();
                } else {
                    $damage +=  $slot->item->base_damage;
                }
            }
        }

        return $damage;
    }
}
