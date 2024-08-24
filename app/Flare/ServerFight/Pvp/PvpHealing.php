<?php

namespace App\Flare\ServerFight\Pvp;

use App\Flare\Models\Character;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class PvpHealing extends PvpBase
{
    private bool $isDefenderVoided = false;

    private array $battleMessages = [];

    public function __construct(private readonly CharacterCacheData $characterCacheData, private readonly CastType $castType)
    {
        parent::__construct($characterCacheData);
    }

    public function setDefenderIsVoided(bool $isVoided): PvpHealing
    {
        $this->isDefenderVoided = $isVoided;

        return $this;
    }

    public function getAttackerMessages(): array
    {
        return $this->battleMessages['attacker'];
    }

    public function getDefenderMessages(): array
    {
        return $this->battleMessages['defender'];
    }

    public function defenderHeal(Character $defender)
    {

        $this->castType->setMonsterHealth($this->attackerHealth);
        $this->castType->setCharacterHealth($this->defenderHealth);
        $this->castType->setCharacterAttackData($defender, $this->isDefenderVoided, ($this->isDefenderVoided ? AttackTypeValue::VOIDED_CAST : AttackTypeValue::CAST));

        $this->castType->healDuringFight($defender, true);

        $this->defenderHealth = $this->castType->getMonsterHealth();
        $this->attackerHealth = $this->castType->getCharacterHealth();

        $this->mergeMessages($this->castType->getDefenderMessages(), 'defender');
        $this->mergeMessages($this->castType->getAttackerMessages(), 'attacker');

        $this->castType->clearMessages();
    }

    public function stealLife(Character $defender)
    {
        if ($defender->classType()->isVampire()) {

            $damage = $this->characterCacheData->getCachedCharacterData($defender, 'dur_modded') * 0.05;

            $this->attackerHealth -= $damage;
            $this->defenderHealth += $damage;

            $this->addDefenderMessage('You lash out in rage and grip the enemies neck. Take what you need child! You deal and heal for: '.number_format($damage), 'player-action');
            $this->addAttackerMessage('The enemy feels the pain of your attack, alas they need your valuable blood to survive! You take: '.number_format($damage).' damage.', 'enemy-action');
        }
    }

    public function addAttackerMessage(string $message, string $type)
    {
        $this->battleMessages['attacker'][] = [
            'message' => $message,
            'type' => $type,
        ];
    }

    public function addDefenderMessage(string $message, string $type)
    {
        $this->battleMessages['defender'][] = [
            'message' => $message,
            'type' => $type,
        ];
    }

    protected function mergeMessages(array $messages, string $key)
    {

        if (! isset($this->battleMessages[$key])) {
            $this->battleMessages[$key] = [];
        }

        $this->battleMessages[$key] = array_merge($this->battleMessages[$key], $messages);
    }
}
