<?php

namespace App\Flare\Handlers;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ClassAttackValue;

class HealingExtraActionHandler {

    private $messages = [];

    public function extraHealing(CharacterInformationBuilder $characterInformationBuilder, int $characterHealth): int {
        $classType = new CharacterClassValue($characterInformationBuilder->getCharacter()->class->name);

        if ($classType->isProphet()) {
            $attackerInfo = (new ClassAttackValue($characterInformationBuilder->getCharacter()))->buildAttackData();

            if (!($this->canUse($attackerInfo['chance']) && $attackerInfo['has_item'])) {
                return $characterHealth;
            }

            $this->messages[] = ['The Lords Blessing washes over you. Your healing spells fire again!'];

            $characterHealth += $characterInformationBuilder->buildHealFor();

            $this->messages[] = ['The Lords Blessing heals you for: ' . number_format($characterInformationBuilder->buildHealFor())];
        }

        return $characterHealth;
    }

    public function getMessages(): array {
        return $this->messages;
    }

    protected function canUse(float $chance): bool {
        $dc = 100 - 100 * $chance;

        return rand(1, 100) > $dc;
    }
}
