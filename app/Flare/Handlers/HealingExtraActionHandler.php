<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;
use App\Game\Adventures\Traits\CreateBattleMessages;

class HealingExtraActionHandler {

    use CreateBattleMessages;

    private $messages = [];

    public function healSpells(CharacterInformationBuilder $characterInformationBuilder, int $characterHealth, array $attackData): int {

        if ($attackData['heal_for'] > 0) {
            $healFor = $attackData['heal_for'];

            $critcialChance = $characterInformationBuilder->getSkill('Criticality');

            $dc = 100 - 100 * $critcialChance;

            if (rand(1, 100) > $dc) {
                $message = 'The heavens open and your wounds start to heal over (Critical heal!)';
                $this->messages = $this->addMessage($message, 'action-fired', $this->messages);

                $healFor *= 2;
            }

            $message          = 'Your healing spell(s) heals for: ' . number_format($healFor);

            $this->messages   = $this->addMessage($message, 'action-fired', $this->messages);

            $characterHealth += $healFor;

            $characterHealth  = $this->extraHealing($characterInformationBuilder, $characterHealth, $attackData);
        }



        return $characterHealth;
    }

    public function extraHealing(CharacterInformationBuilder $characterInformationBuilder, int $characterHealth, array $attackData): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isProphet()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $characterHealth;
            }

            $message          = 'The Lords Blessing washes over you. Your healing spells fire again!';

            $this->messages   = $this->addMessage($message, 'enemy-action-fired', $this->messages);

            $healFor          = $attackData['heal_for'];

            $critcialChance   = $characterInformationBuilder->getSkill('Criticality');

            $dc = 100 - 100 * $critcialChance;

            if (rand(1, 100) > $dc) {
                $message = 'The heavens open and your wounds start to heal over (Critical heal!)';
                $this->messages = $this->addMessage($message, 'enemy-action-fired', $this->messages);

                $healFor *= 2;
            }

            $characterHealth += $healFor;

            $message          = 'Your healing spell(s) heals for: ' . number_format($healFor);

            $this->messages   = $this->addMessage($message, 'enemy-action-fired', $this->messages);
        }

        return $characterHealth;
    }

    public function getMessages(): array {
        return $this->messages;
    }

    public function resetMessages() {
        $this->messages = [];
    }

    protected function canUse(float $chance): bool {
        $dc = 100 - 100 * $chance;

        return rand(1, 100) > $dc;
    }
}
