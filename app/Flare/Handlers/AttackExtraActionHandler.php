<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;
use App\Game\Adventures\Traits\CreateBattleMessages;

class AttackExtraActionHandler {

    use CreateBattleMessages;

    private array $messages = [];

    private $characterHealth = null;

    public function setCharacterhealth(int $characterhealth): AttackExtraActionHandler {
        $this->characterHealth = $characterhealth;

        return $this;
    }

    public function getCharacterHealth() {
        return $this->characterHealth;
    }

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

    public function castSpells(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, $defender, bool $voided = false): int {
        $spellDamage = $characterInformationBuilder->getTotalSpellDamage($voided);

        $monsterCurrentHealth = $this->spellDamage($spellDamage, $monsterCurrentHealth, $defender, $characterInformationBuilder->getCharacter());

        return $this->doubleCastChance($characterInformationBuilder, $monsterCurrentHealth, $defender, $voided);
    }

    public function getMessages(): array {
        return $this->messages;
    }

    public function resetMessages() {
        $this->characterHealth = null;
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

            $damage = $characterInformationBuilder->buildAttack($voided) * 0.15;

            for ($i = 1; $i <= 3; $i++) {
                $monsterCurrentHealth -= $damage;

                $message = $character->name . ' hit for (weapon): ' . number_format($damage);

                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
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

            $totalDamage = ($characterAttack + $characterAttack * 0.15);

            $monsterCurrentHealth -= $characterAttack;

            $message        = $characterInformationBuilder->getCharacter()->name . ' hit for (weapon): ' . number_format($totalDamage);
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
        }

        return $monsterCurrentHealth;
    }

    protected function doubleCastChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, $defender, bool $voided = false): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isHeretic()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $this->messages[] = ['Magic crackles through the air as you cast again!'];

            $spellDamage = $characterInformationBuilder->getTotalSpellDamage($voided);

            $monsterCurrentHealth = $this->spellDamage($spellDamage, $monsterCurrentHealth, $defender, $characterInformationBuilder->getCharacter(), $voided);
        }

        return $monsterCurrentHealth;
    }

    public function vampireThirst(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isVampire()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!$this->canUse($attackerInfo['chance'])) {
                return $monsterCurrentHealth;
            }

            $message        = 'There is a thirst child, its in your soul! Lash out and kill!';
            $this->messages = $this->addMessage($message, 'action-fired', $this->messages);

            if (!$voided) {
                $dur = $characterInformationBuilder->statMod('dur');
            } else {
                $dur = $characterInformationBuilder->getCharacter()->dur;
            }

            $character = $characterInformationBuilder->getCharacter();

            $totalAttack = round($dur - $dur * 0.15);

            $monsterCurrentHealth -= $totalAttack;
            $this->characterHealth += $totalAttack;

            $message        = $character->name . ' hit for (and healed for) (thirst!) ' . number_format($totalAttack);
            $this->messages = $this->addMessage($message, 'action-fired', $this->messages);
        }

        return $monsterCurrentHealth;
    }

    protected function spellDamage(int $spellDamage, int $monsterCurrentHealth, $defender, Character $character, bool $voided = false): int {
        $totalDamage = $this->calculateSpellDamage($spellDamage, $defender, $character, $voided);

        if ($totalDamage > 0) {
            $criticalChance = $character->getInformation()->getSkill('Criticality');

            $critDc = 100 - 100 * $criticalChance;

            if (rand(1, 100) > $critDc) {
                $message        = 'Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)';
                $this->messages =  $this->addMessage($message, 'action-fired', $this->messages);

                $totalDamage *= 2;
            }

            $health = $monsterCurrentHealth - $totalDamage;

            if ($health < 0) {
                $health = 0;
            }

            $monsterCurrentHealth = $health;

            $message = 'Your spells hit the enemy for: ' . number_format($totalDamage);
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
        }

        return $monsterCurrentHealth;
    }

    private function canUse(float $chance): bool {
        $dc = 100 - 100 * $chance;

        return rand(1, 100) > $dc;
    }

    private function calculateSpellDamage(int $spellDamage, $defender, Character $character, bool $voided = false): int {
        $spellEvasion = (float) $defender->spell_evasion;
        $dc           = 100 - 100 * $spellEvasion;
        $maxRole      = 100;
        $classType    = $character->classType();

        if ($classType->isProphet() || $classType->isHeretic()) {
            $castingAccuracyBonus = $character->getInformation()->getSkill('Casting Accuracy');
            $maxRole              = ($voided ? $character->focus : $character->getInformation()->statMod('focus')) * (.05 + $castingAccuracyBonus);
            $dc                   = $maxRole - $maxRole * $spellEvasion;
        }

        if ($dc <= 0 || rand(0, $maxRole) > $dc) {
            return 0;
        }

        return $spellDamage;

    }

    private function weaponAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false): int {
        $characterAttack = $characterInformationBuilder->buildAttack($voided);

        $dc = 100 - 100 * $characterInformationBuilder->getSkill('Criticality');

        if (rand(1, 100) > $dc) {
            $characterAttack *= 2;

            $message = 'You become overpowered with rage! (Critical strike!)';

            $this->messages = $this->addMessage($message, 'action-fired', $this->messages);
        }

        $monsterCurrentHealth -= $characterAttack;

        $character = $characterInformationBuilder->getCharacter();

        $message = $character->name . ' hit for (weapon): ' . number_format($characterAttack);

        $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

        return $monsterCurrentHealth;
    }
}
