<?php

namespace App\Game\Core\Values\View;

use App\Flare\Models\Character;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Support\Str;

class ClassBonusInformation
{
    public function buildClassBonusDetailsForInfo(string $className): array
    {
        $classAttackValue = new CharacterClassValue($className);

        $details = [
            'base_chance' => 0.05,
        ];

        $details = array_merge($details, $this->getClassDetails($classAttackValue, $details));

        return $details;
    }

    public function buildClassBonusDetails(Character $character): array
    {
        $classAttackValue = new CharacterClassValue($character->class->name);

        $information = resolve(CharacterStatBuilder::class)->setCharacter($character);

        $details = [
            'base_chance' => 0.05,
        ];

        $details = array_merge($details, $this->getClassDetails($classAttackValue, $details));

        $details['base_chance'] = $details['base_chance'] + $information->classBonus();

        return $details;
    }

    protected function getClassDetails(CharacterClassValue $classAttackValue, array $details): array
    {
        if ($classAttackValue->isFighter()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::FIGHTERS_DOUBLE_DAMAGE);
            $details['requires'] = 'Dual Weapon equipped or Weapon/Shield equipped';
            $details['description'] = 'With a weapon equipped you have a small chance to do damage equal to your modded attack + 15% of the modded attack, without being blocked. With a shield equipped you use your class bonus towards your defence automatically.';
        }

        if ($classAttackValue->isRanger()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::RANGER_TRIPLE_ATTACK);
            $details['requires'] = 'Bow equipped';
            $details['description'] = 'With a bow equipped you have a small chance to attack 3 additional times with the bow, without being blocked.';
        }

        if ($classAttackValue->isThief()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::THIEVES_SHADOW_DANCE);
            $details['requires'] = 'Dual daggers equipped';
            $details['description'] = 'With dual daggers equipped, you have a chance to slip by the enemy and instantly hit them.';
        }

        if ($classAttackValue->isHeretic()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::HERETICS_DOUBLE_CAST);
            $details['requires'] = 'One or two wands equipped';
            $details['description'] = 'With a damage spell equipped you have a small chance to cast another spell. Enemies cannot avoid this.';
        }

        if ($classAttackValue->isProphet()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::PROPHET_HEALING);
            $details['requires'] = 'One or two censers equipped';
            $details['description'] = 'With a healing spell equipped you have a small chance to have the Lords blessing bestowed upon you. Your healing spells will fire again.';
        }

        if ($classAttackValue->isVampire()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::VAMPIRE_THIRST);
            $details['requires'] = 'One or two claws equipped';
            $details['description'] = 'Every time you attack, you have a chance to fire off the thirst which can steal 15% of your dur from the enemy while attacking and defending.';
        }

        if ($classAttackValue->isBlacksmith()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::BLACKSMITHS_HAMMER_SMASH);
            $details['requires'] = 'Hammer';
            $details['description'] = 'Every time you attack you have chance, based on class bonus, to do whats called a Hammer Smash. This will will first do 30% of your modded Strength. You then have a 1/100 chance at 60% bonus to then do three additional attacks, called After Shocks. Each After Shock will reduce the previous damage by 15%.';
        }

        if ($classAttackValue->isArcaneAlchemist()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::ARCANE_ALCHEMISTS_DREAMS);
            $details['requires'] = 'Stave';
            $details['description'] = 'Every time you attack you have chance to, based on class bonus and with a stave equipped, to do Alchemists Ravenous Dream. This can do 10% of your int followed by a reduction of 3% for each additional attack between 2 and 6 times.';
        }

        if ($classAttackValue->isPrisoner()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::PRISONER_RAGE);
            $details['requires'] = 'Weapons';
            $details['description'] = 'With a weapon (or two) equipped you have a chance to rage out at the enemy for 15% of your strength 1-4 times';
        }

        if ($classAttackValue->isAlcoholic()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::ALCOHOLIC_PUKE);
            $details['requires'] = 'No Weapons';
            $details['description'] = 'With no weapons (including: Staves, Hammers and Bows) and spells (damage or healing) equipped you have a chance to do Bloody Puke, which deals 30% of your health as damage but you also suffer 15% in damage.';
        }

        if ($classAttackValue->isMerchant()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::MERCHANTS_SUPPLY);
            $details['requires'] = 'Stave or Bow';
            $details['description'] = 'With a stave or bow equipped you have a chance to do one of two attacks based on a coin flip. One is 2x your damage and the other is 4x your damage.';
        }

        if ($classAttackValue->isDancer()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::SENSUAL_DANCE);
            $details['requires'] = 'Fans';
            $details['description'] = 'With one or two fans you can dance around the enemy and sensually lure them to their own bloody death.';
        }

        if ($classAttackValue->isCleric()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::HOLY_SMITE);
            $details['requires'] = 'Maces';
            $details['description'] = 'Pray to the one true god. Follow in the foot steps of The Church. Follow the orders of The Federation and slay the beats before you in divine light.';
        }

        if ($classAttackValue->isGunslinger()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::GUNSLINGERS_ASSASSINATION);
            $details['requires'] = 'Guns';
            $details['description'] = 'Take aim child, take aim and may your bullets from the old world strike down the enemy.';
        }

        if ($classAttackValue->isBookBinder()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::BOOK_BINDERS_FEAR);
            $details['requires'] = 'Scratch Awls';
            $details['description'] = 'Fear, it\'s what keeps us alive child. Fear is what makes you lash out and violently stab your enemy over and over and over again, just to live.';
        }

        if ($classAttackValue->isApothecary()) {
            $details['type'] = Str::ucfirst(ClassAttackValue::PLAGUE_SURGE);
            $details['requires'] = 'Censor & Dagger';
            $details['description'] = 'In the shadows, the magics you cast to heal the sick become corrupted and twisted. A plague seeps into the light, posioning the innocent and the wicked alike.';
        }

        return $details;
    }
}
