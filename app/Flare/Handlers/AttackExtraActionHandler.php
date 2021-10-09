<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;
use App\Game\Adventures\Traits\CreateBattleMessages;

class AttackExtraActionHandler {

    use CreateBattleMessages;

    private array $messages = [];

    public function doAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false): int {

        $monsterCurrentHealth = $this->weaponAttack($characterInformationBuilder, $monsterCurrentHealth, $voided);
        $monsterCurrentHealth = $this->tripleAttackChance($characterInformationBuilder, $monsterCurrentHealth, $voided);
        $monsterCurrentHealth = $this->doubleAttackChance($characterInformationBuilder, $monsterCurrentHealth, $voided);

        return $this->vampireThirst($characterInformationBuilder, $monsterCurrentHealth, $voided);
    }

    public function canAutoAttack(CharacterInformationBuilder $characterInformationBuilder): bool {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isThief()) {
            $chance = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData()['chance'];

            $dc = 100 - 100 * $chance;

            return rand (1, 100) > $dc;
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

    public function resetMessages() {
        $this->messages = [];
    }


    protected function tripleAttackChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isRanger()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $message        = 'A fury takes over you. You notch the arrows thrice at the enemies direction';
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            for ($i = 1; $i <= 3; $i++) {
                $monsterCurrentHealth = $this->weaponAttack($characterInformationBuilder, $monsterCurrentHealth, $voided);
            }
        }

        return $monsterCurrentHealth;
    }

    protected function doubleAttackChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isFighter()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $message        = 'The strength of your rage courses through your veins!';
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            $characterAttack = $characterInformationBuilder->buildAttack($voided);

            $totalDamage = ($characterAttack + $characterAttack * 0.05);

            $monsterCurrentHealth -= $characterAttack;

            $message        = $characterInformationBuilder->getCharacter()->name . ' hit for (weapon): ' . number_format($totalDamage);
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
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

    protected function vampireThirst(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isVampire()) {

            $message        = 'There is a thirst child, its in your soul! Lash out and kill!';
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            if (!$voided) {
                $dur = $characterInformationBuilder->statMod('dur');
            } else {
                $dur = $characterInformationBuilder->getCharacter()->dur;
            }

            $character = $characterInformationBuilder->getCharacter();

            $totalAttack = round($dur - $dur * 0.95);

            $monsterCurrentHealth -= $totalAttack;

            $message        = $character->name . ' hit for (thirst!) ' . number_format($totalAttack);
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
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

            $this->messages[] =  [
                'Your spells hit the enemy for: ' . $totalDamage,
            ];

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
        $spellEvasion = (float) $defender->spell_evasion;
        $dc = 100 - $spellEvasion;

        if ($dc <= 0 || rand(0, 100) > $dc) {
            return 0;
        }

        return $spellDamage;

    }

    private function weaponAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false): int {
        $characterAttack = $characterInformationBuilder->buildAttack($voided);

        $monsterCurrentHealth -= $characterAttack;

        $character = $characterInformationBuilder->getCharacter();

        $message = $character->name . ' hit for (weapon): ' . number_format($characterAttack);

        $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

        return $monsterCurrentHealth;
    }
}
