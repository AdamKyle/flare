<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;

class AttackExtraActionHandler {

    private array $messages = [];

    public function doAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth): int {

        $monsterCurrentHealth = $this->weaponAttack($characterInformationBuilder, $monsterCurrentHealth);
        $monsterCurrentHealth = $this->tripleAttackChance($characterInformationBuilder, $monsterCurrentHealth);
        $monsterCurrentHealth = $this->doubleAttackChance($characterInformationBuilder, $monsterCurrentHealth);

        return $this->vampireThirst($characterInformationBuilder, $monsterCurrentHealth);
    }

    public function canAutoAttack(CharacterInformationBuilder $characterInformationBuilder): bool {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isThief()) {
            $chance = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData()['chance'];

            $this->messages[] = ['You dance along in the shadows, the enemy doesn\'t see you. Strike now!'];

            return true;
        }

        return false;
    }

    public function castSpells(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, $defender): int {
        $spellDamage = $characterInformationBuilder->getTotalSpellDamage();

        $monsterCurrentHealth = $this->spellDamage($spellDamage, $monsterCurrentHealth, $defender);

        return $this->doubleCastChance($characterInformationBuilder, $monsterCurrentHealth, $defender);
    }

    public function getMessages(): array {
        return $this->messages;
    }


    protected function tripleAttackChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isRanger()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $this->messages[] = ['A fury takes over you. You notch the arrows thrice at the enemies direction'];

            for ($i = 1; $i <= 3; $i++) {
                $monsterCurrentHealth = $this->weaponAttack($characterInformationBuilder, $monsterCurrentHealth);
            }
        }

        return $monsterCurrentHealth;
    }

    protected function doubleAttackChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isFighter()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $this->messages[] = ['The strength of your rage courses through your veins!'];

            $characterAttack = $characterInformationBuilder->buildAttack();

            $totalDamage = ($characterAttack + $characterAttack * 0.05);

            $monsterCurrentHealth -= $characterAttack;

            $this->messages[] = [$characterInformationBuilder->getCharacter()->name . ' hit for (weapon): ' . number_format($totalDamage)];
        }

        return $monsterCurrentHealth;
    }

    protected function doubleCastChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, $defender): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isHeretic()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $this->messages[] = ['Magic crackles through the air as you cast again!'];

            $spellDamage = $characterInformationBuilder->getTotalSpellDamage();

            $monsterCurrentHealth = $this->spellDamage($spellDamage, $monsterCurrentHealth, $defender);
        }

        return $monsterCurrentHealth;
    }

    protected function vampireThirst(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isVampire()) {
            $this->messages[] = ['There is a thirst child, its in your soul! Lash out and kill!'];

            $dur = $characterInformationBuilder->statMod('dur');
            $character = $characterInformationBuilder->getCharacter();

            $totalAttack = round($dur - $dur * 0.95);

            $monsterCurrentHealth -= $totalAttack;

            $this->messages[] = [$character->name . ' hit for (thirst!) ' . number_format($totalAttack)];
        }

        return $monsterCurrentHealth;
    }

    protected function spellDamage(int $spellDamage, int $monsterCurrentHealth, $defender,): int {
        $totalDamage = $this->calculateSpellDamage($spellDamage, $defender);

        if ($totalDamage > 0) {
            $health = $monsterCurrentHealth - $totalDamage;

            if ($health < 0) {
                $health = 0;
            }

            $monsterCurrentHealth = $health;

            if ($spellDamage !== $totalDamage) {
                $this->messages[] = [
                    'Your spells hit the enemy for: ' . $totalDamage . ' (Partially Annulled)',
                ];
            } else {
                $this->messages[] =  [
                    'Your spells hit the enemy for: ' . $totalDamage,
                ];
            }

        } else {
            $this->messages[] =  [
                'Your spells have no effect ...'
            ];
        }

        return $monsterCurrentHealth;
    }

    private function canUse(float $chance): bool {
        $dc = 100 - 100 * $chance;

        return rand(1, 100) > $dc;
    }

    private function calculateSpellDamage(int $spellDamage, $defender): int {
        return ceil($spellDamage - ($spellDamage * $defender->spell_evasion));
    }

    private function weaponAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth): int {
        $characterAttack = $characterInformationBuilder->buildAttack();

        $monsterCurrentHealth -= $characterAttack;

        if ($characterInformationBuilder->hasAffixes()) {
            $this->messages[] = ['The enchantments on your equipment lash out at the enemy!'];
        }

        $character = $characterInformationBuilder->getCharacter();

        $this->messages[] = [$character->name . ' hit for (weapon): ' . number_format($characterAttack)];

        return $monsterCurrentHealth;
    }
}
