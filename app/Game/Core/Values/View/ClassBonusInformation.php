<?php

namespace App\Game\Core\Values\View;

use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;
use Illuminate\Support\Str;

class ClassBonusInformation {

    public function buildClassBonusDetails(string $className): array {
        $classAttackValue = new CharacterClassValue($className);

        $details = [
            'base_chance' => 0.05,
        ];

        if ($classAttackValue->isFighter()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::FIGHTERS_DOUBLE_DAMAGE);
            $details['requires'] = 'Weapon equipped';
            $details['description'] = 'With a weapon equipped you have a small chance to do damage equal to your modded attack + 5% of the modded attack, with out being blocked.';
        }

        if ($classAttackValue->isRanger()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::RANGER_TRIPLE_ATTACK);
            $details['requires'] = 'Bow equipped';
            $details['description'] = 'With a bow equipped you have a small chance to attack 3 additional times with the bow, with out being blocked.';
        }

        if ($classAttackValue->isThief()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::RANGER_TRIPLE_ATTACK);
            $details['requires'] = 'Duel weapons equipped';
            $details['description'] = 'With duel weapons equipped, you have a chance to slip by the enemy and instantly hit them.';
        }

        if ($classAttackValue->isHeretic()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::HERETICS_DOUBLE_CAST);
            $details['requires'] = 'Damage spell equipped';
            $details['description'] = 'With a damage spell equipped you have a small chance to cast another spell. Spell evasion does count.';
        }

        if ($classAttackValue->isProphet()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::PROPHET_HEALING);
            $details['requires'] = 'Healing spell equipped';
            $details['description'] = 'With a healing spell equipped you have a small chance to have the Lords blessing bestowed upon you. Your healing spells will fire again.';
        }

        if ($classAttackValue->isVampire()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::VAMPIRE_THIRST);
            $details['requires'] = 'N/A';
            $details['description'] = 'Everytime you attack, you will do an additional 5% of your durability. This will fire automatically.';
        }

        return $details;
    }
}
