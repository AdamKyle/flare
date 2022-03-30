<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Handlers\Values\CounterDeathValue;
use App\Flare\Models\Character;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;
use App\Game\Adventures\Traits\CreateBattleMessages;

class AttackExtraActionHandler {

    use CreateBattleMessages;

    private $counterHandler;

    private array $messages = [];

    private $characterHealth = null;

    public function __construct(CounterHandler $counterHandler) {
        $this->counterHandler = $counterHandler;
    }

    public function setCharacterhealth(int $characterhealth): AttackExtraActionHandler {
        $this->characterHealth = $characterhealth;

        return $this;
    }

    public function getCharacterHealth() {
        return $this->characterHealth;
    }

    public function doAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false, float $dmgReduction = 0.0): int {

        $monsterCurrentHealth = $this->weaponAttack($characterInformationBuilder, $monsterCurrentHealth, $voided, $dmgReduction);
        $monsterCurrentHealth = $this->tripleAttackChance($characterInformationBuilder, $monsterCurrentHealth, $voided, $dmgReduction);
        $monsterCurrentHealth = $this->doubleAttackChance($characterInformationBuilder, $monsterCurrentHealth, $voided, $dmgReduction);
        $monsterCurrentHealth = $this->hammerSmashAttack($characterInformationBuilder, $monsterCurrentHealth, $voided, $dmgReduction);
        $monsterCurrentHealth = $this->alchemistsRavenousDream($characterInformationBuilder, $monsterCurrentHealth, $voided, $dmgReduction);

        return $this->vampireThirst($characterInformationBuilder, $monsterCurrentHealth, $voided);
    }

    public function canAutoAttack(CharacterInformationBuilder $characterInformationBuilder): bool {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isThief()) {
            $chance = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData()['chance'];

            if ($chance >= 1.0) {
                return true;
            }

            $dc = 100 - 100 * $chance;

            return rand (1, 100) > $dc;
        }

        return false;
    }

    public function castSpells(CharacterInformationBuilder $characterInformationBuilder, $defender, int $monsterCurrentHealth, bool $voided = false, float $dmgReduction = 0.0): array {

        $spellDamage = $characterInformationBuilder->getTotalSpellDamage($voided);

        $monsterCurrentHealth = $this->spellDamage($spellDamage, $monsterCurrentHealth, $defender, $characterInformationBuilder->getCharacter(), $voided, false, $dmgReduction);

        if ($spellDamage > 0.0) {
            $healthObject = $this->counterHandler->enemyCountersPlayer($defender, $characterInformationBuilder->getCharacter(), $monsterCurrentHealth, $this->characterHealth, $voided);

            $this->characterHealth = $healthObject['character_health'];
            $monsterCurrentHealth  = $healthObject['monster_health'];

            $this->messages = [...$this->messages, ...$this->counterHandler->getMessages()];

            if ($monsterCurrentHealth <= 0) {

                return [
                    'status'         => CounterDeathValue::MONSTER_DIED,
                    'monster_health' => $monsterCurrentHealth,
                ];
            }

            if ($this->characterHealth <= 0) {
                return [
                    'status'         => CounterDeathValue::CHARACTER_DIED,
                    'monster_health' => $monsterCurrentHealth,
                ];
            }
        }

        return [
            'status' => 0,
            'monster_health' => $this->doubleCastChance($characterInformationBuilder, $monsterCurrentHealth, $defender, $voided, $dmgReduction),
        ];
    }

    public function positionalWeaponAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, int $damage): int {

        $dc = 100 - 100 * $characterInformationBuilder->getSkill('Criticality');

        if (rand(1, 100) > $dc) {
            $damage *= 2;

            $message = 'You become overpowered with rage! (Critical strike!)';

            $this->messages = $this->addMessage($message, 'action-fired', $this->messages);
        }

        $monsterCurrentHealth -= $damage;

        $character = $characterInformationBuilder->getCharacter();

        $message = $character->name . ' hit for (weapon(s)): ' . number_format($damage);

        $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

        return $this->positionalDoubleAttack($characterInformationBuilder, $monsterCurrentHealth, $damage);
    }

    public function positionalDoubleAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, int $damage) {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isFighter()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            for ($i = 1; $i <= 2; $i++) {
                $message = 'The strength of your rage courses through your veins!';
                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

                $totalDamage = ($damage + $damage * 0.15);

                $monsterCurrentHealth -= $totalDamage;

                $message = $characterInformationBuilder->getCharacter()->name . ' hit for (weapon): ' . number_format($totalDamage);
                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
            }
        }

        return $monsterCurrentHealth;
    }

    public function getMessages(): array {
        return $this->messages;
    }

    public function resetMessages() {
        $this->characterHealth = null;
        $this->messages = [];
    }


    protected function tripleAttackChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false, float $dmgReduction = 0.0): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);
        $character = $characterInformationBuilder->getCharacter();

        if ($classType->isRanger()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $message        = 'A fury takes over you. You notch the arrows thrice at the enemies direction';
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            $damage = $characterInformationBuilder->getTotalWeaponDamage($voided) * 0.15;

            $damage -= $damage * $dmgReduction;

            for ($i = 1; $i <= 3; $i++) {
                $monsterCurrentHealth -= $damage;

                $message = $character->name . ' hit for (weapon): ' . number_format($damage);

                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
            }
        }

        return $monsterCurrentHealth;
    }

    protected function hammerSmashAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false, float $dmgReduction = 0.0): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isBlacksmith() && !$voided) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $message = 'You raise your mighty hammer high above your head and bring it crashing down!';
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            $initialDamage = $characterInformationBuilder->statMod('str') * 0.30;

            if ($dmgReduction > 0.0) {
                $message        = 'The Plane weakens your ability to do full damage!';
                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

                $initialDamage -= $initialDamage * $dmgReduction;
            }

            $monsterCurrentHealth -= $initialDamage;

            $message = $characterInformationBuilder->getCharacter()->name . ' hit for (Hammer): ' . number_format($initialDamage);
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            $rollForAfterShock = rand(1, 100);
            $rollForAfterShock += $rollForAfterShock * .60;

            if ($rollForAfterShock > 99) {
                $message = 'The enemy feels the after shocks of the Hammer Smash!';
                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

                for ($i = 3; $i > 0; $i--) {
                    $initialDamage -= $initialDamage * 0.15;

                    $monsterCurrentHealth -= $initialDamage;

                    $message = 'Aftershock hit for: ' . number_format($initialDamage) . '!';
                    $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
                }
            }
        }

        return $monsterCurrentHealth;
    }

    protected function alchemistsRavenousDream(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false, float $dmgReduction = 0.0): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isArcaneAlchemist() && !$voided) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $message = 'The world around you fades to blackness, your eyes glow red with rage. The enemy trembles.';
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            $initialDamage = $characterInformationBuilder->statMod('int') * 0.10;

            if ($dmgReduction > 0.0) {
                $message        = 'The Plane weakens your ability to do full damage!';
                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

                $initialDamage -= $initialDamage * $dmgReduction;
            }

            $monsterCurrentHealth -= $initialDamage;

            $message = $characterInformationBuilder->getCharacter()->name . ' hit for (Arcane Alchemist Ravenous Dream): ' . number_format($initialDamage);
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            $times                = rand(2, 6);
            $originalTimes        = $times;
            $additionalPercentage = 0.13;

            $message = 'The earth shakes as you cause a multitude of explosions to engulf the enemy.';
            $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

            while ($times > 0) {
                if ($times === $originalTimes) {
                    $monsterCurrentHealth -= $initialDamage;

                    $message = $characterInformationBuilder->getCharacter()->name . ' hit for (Arcane Alchemist Ravenous Dream): ' . number_format($initialDamage);
                    $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
                } else {
                    $initialDamage = $characterInformationBuilder->statMod('int') * $additionalPercentage;

                    if ($dmgReduction > 0.0) {
                        $message        = 'The Plane weakens your ability to do full damage!';
                        $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

                        $initialDamage -= $initialDamage * $dmgReduction;
                    }

                    $monsterCurrentHealth -= $initialDamage;
                    $additionalPercentage += 0.03;
                }

                $times--;
            }
        }

        return $monsterCurrentHealth;
    }

    protected function doubleAttackChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false, float $dmgReduction = 0.0): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isFighter()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            for ($i = 1; $i <= 2; $i++) {
                $message = 'The strength of your rage courses through your veins!';
                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

                $characterAttack = $characterInformationBuilder->getTotalWeaponDamage($voided);

                $totalDamage = ($characterAttack + $characterAttack * 0.15);

                $totalDamage -= $totalDamage * $dmgReduction;

                $monsterCurrentHealth -= $totalDamage;

                $message = $characterInformationBuilder->getCharacter()->name . ' hit for (weapon): ' . number_format($totalDamage);
                $this->messages = $this->addMessage($message, 'info-damage', $this->messages);
            }
        }

        return $monsterCurrentHealth;
    }

    protected function doubleCastChance(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, $defender, bool $voided = false, float $dmgReduction): int {
        if ($defender->spell_evasion > 1.0) {
            return $monsterCurrentHealth;
        }

        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isHeretic()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $monsterCurrentHealth;
            }

            $message               = 'Magic crackles through the air as you cast again!';
            $this->messages        = $this->addMessage($message, 'action-fired', $this->messages);

            $spellDamage           = $characterInformationBuilder->getTotalSpellDamage($voided);
            $spellDamage           = $spellDamage + $spellDamage * 0.15;
            $spellDamage          -= $spellDamage * $dmgReduction;
            $monsterCurrentHealth -= $spellDamage;

            $message               = 'Your spell(s) hit for: ' . number_format($spellDamage);
            $this->messages        = $this->addMessage($message, 'info-damage', $this->messages);
        }

        return $monsterCurrentHealth;
    }

    public function vampireThirst(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false, float $dmgReduction = 0.0): int {
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

            $totalAttack  = round($dur - $dur * 0.15);
            $totalAttack -= $totalAttack * $dmgReduction;

            $monsterCurrentHealth -= $totalAttack;
            $this->characterHealth += $totalAttack;

            $message        = $character->name . ' hit for (and healed for) (thirst!) ' . number_format($totalAttack);
            $this->messages = $this->addMessage($message, 'action-fired', $this->messages);
        }

        return $monsterCurrentHealth;
    }

    protected function spellDamage(int $spellDamage, int $monsterCurrentHealth, $defender, Character $character, bool $voided = false, bool $isDoubleCast = false, float $dmgReduction = 0.0): int {

        $totalDamage = $this->calculateSpellDamage($spellDamage, $defender, $character, $voided);

        if ($totalDamage > 0) {
            $criticalChance = $character->getInformation()->getSkill('Criticality');

            $critDc = 100 - 100 * $criticalChance;

            if (rand(1, 100) > $critDc) {
                $message        = 'Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)';
                $this->messages =  $this->addMessage($message, 'action-fired', $this->messages);

                $totalDamage *= 2;
            }

            if ($isDoubleCast) {
                $totalDamage += $totalDamage * 0.15;
            }

            $totalDamage -= $totalDamage * $dmgReduction;

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
        if ($chance >= 1.0) {
            return true;
        }

        $dc = 100 - 100 * $chance;

        return rand(1, 100) > $dc;
    }

    private function calculateSpellDamage(int $spellDamage, $defender, Character $character, bool $voided = false): int {
        $spellEvasion         = (float) $defender->spell_evasion;
        $dc                   = 100;
        $roll                 = rand(1, 100);
        $classType            = $character->classType();
        $castingAccuracyBonus = $character->getInformation()->getSkill('Casting Accuracy');
        $focus                = $character->getInformation()->statMod('focus');

        if ($spellEvasion >= 1.0) {
            $this->messages = $this->addMessage('The enemy evades your spells', 'enemy-action-fired', $this->messages);

            return 0.0;
        }

        if ($voided) {
            $focus = $character->focus;
        }

        if ($classType->isProphet() || $classType->isHeretic()) {
            $focus                 = $focus * 0.02;
            $castingAccuracyBonus += $focus;
        }

        $castingAccuracyBonus = $castingAccuracyBonus - $spellEvasion;

        if ($castingAccuracyBonus < 0.0) {
            $castingAccuracyBonus = 0.0;
        }

        $dc -= $dc * $castingAccuracyBonus;

        if ($dc <= 0.0) {
            return $spellDamage;
        }

        if ($roll > $dc) {
            return $spellDamage;
        }

        return 0.0;
    }

    private function weaponAttack(CharacterInformationBuilder $characterInformationBuilder, int $monsterCurrentHealth, bool $voided = false, float $dmgReduction = 0.0): int {
        $characterAttack = $characterInformationBuilder->getTotalWeaponDamage($voided);

        $dc = 100 - 100 * $characterInformationBuilder->getSkill('Criticality');

        if (rand(1, 100) > $dc) {
            $characterAttack *= 2;

            $message = 'You become overpowered with rage! (Critical strike!)';

            $this->messages = $this->addMessage($message, 'action-fired', $this->messages);
        }

        $characterAttack -= $characterAttack * $dmgReduction;

        $monsterCurrentHealth -= $characterAttack;

        $character = $characterInformationBuilder->getCharacter();

        $message = $character->name . ' hit for (weapon): ' . number_format($characterAttack);

        $this->messages = $this->addMessage($message, 'info-damage', $this->messages);

        return $monsterCurrentHealth;
    }
}
