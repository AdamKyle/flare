<?php

namespace App\Game\Core\Values\View;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;
use Illuminate\Support\Str;

class ClassBonusInformation {

    public function buildClassBonusDetailsForInfo(string $className): array {
        $classAttackValue = new CharacterClassValue($className);

        $details = [
            'base_chance' => 0.05,
        ];

        $details = array_merge($details,  $this->getClassDetails($classAttackValue, $details));

        return $details;
    }

    public function buildClassBonusDetails(Character $character): array {
        $classAttackValue = new CharacterClassValue($character->class->name);

        $information = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        $details = [
            'base_chance' => 0.05,
        ];

        $details = array_merge($details,  $this->getClassDetails($classAttackValue, $details));

        $details['base_chance'] = $details['base_chance'] + $information->classBonus();

        return $details;
    }

    protected function getClassDetails(CharacterClassValue $classAttackValue, array $details): array  {
        if ($classAttackValue->isFighter()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::FIGHTERS_DOUBLE_DAMAGE);
            $details['requires'] = 'Duel Weapon equipped or Weapon/Shield equipped';
            $details['description'] = 'With a weapon equipped you have a small chance to do damage equal to your modded attack + 15% of the modded attack, with out being blocked. With a shield equipped you have will use your class bonus towards your defence.';
        }

        if ($classAttackValue->isRanger()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::RANGER_TRIPLE_ATTACK);
            $details['requires'] = 'Bow equipped';
            $details['description'] = 'With a bow equipped you have a small chance to attack 3 additional times with the bow, with out being blocked.';
        }

        if ($classAttackValue->isThief()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::THIEVES_SHADOW_DANCE);
            $details['requires'] = 'Duel weapons equipped';
            $details['description'] = 'With duel weapons equipped, you have a chance to slip by the enemy and instantly hit them.';
        }

        if ($classAttackValue->isHeretic()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::HERETICS_DOUBLE_CAST);
            $details['requires'] = 'Damage spell equipped';
            $details['description'] = 'With a damage spell equipped you have a small chance to cast another spell. Enemies cannot avoid this.';
        }

        if ($classAttackValue->isProphet()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::PROPHET_HEALING);
            $details['requires'] = 'Healing spell equipped';
            $details['description'] = 'With a healing spell equipped you have a small chance to have the Lords blessing bestowed upon you. Your healing spells will fire again.';
        }

        if ($classAttackValue->isVampire()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::VAMPIRE_THIRST);
            $details['requires'] = 'N/A';
            $details['description'] = 'Everytime you attack, you have a chance to fire off the thirst which can steal 15% of your dur from the enemy as both attack and healing.';
        }

        return $details;
    }
}
