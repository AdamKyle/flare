<?php
namespace App\Flare\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;

class FightService {

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Monster $monster
     */
    private $monster;

    /**
     * @var array $logInformation
     */
    private $logInformation = [];

    /**
     * @var int $currentCharacterhealth
     */
    private $currentCharacterHealth = 0;

    /**
     * @var int $currentMonsterHealth
     */
    private $currentMonsterHealth   = 0;

    /**
     * @var CharacterInformationBuilder $characterInformation
     */
    private $characterInformation;

    /**
     * Used to stop adventures from going on too long.
     *
     * @var int $counter
     */
    private $counter = 0;

    /**
     * used to stop adventures from going on too long.
     *
     * @var bool $tookTooLong
     */
    private $tookTooLong = false;

    /**
     * @param Character $character
     * @param Monster $monster
     * @return void
     */
    public function __construct(Character $character, Monster $monster) {
        $this->character = $character;
        $this->monster   = $monster;

        // Set character information
        $this->characterInformation   = resolve(CharacterInformationBuilder::class)->setCharacter($character);
        $this->currentCharacterHealth = $this->characterInformation->buildHealth();

        // Set monster information
        $healthRange                = explode('-', $this->monster->health_range);
        $this->currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + 10;
    }

    /**
     * Get the log information.
     *
     * @return array
     */
    public function getLogInformation(): array {
        return $this->logInformation;
    }

    /**
     * Reset the log information
     *
     * @return void
     */
    public function resetLogInfo(): void {
        $this->logInformation = [];
    }

    /**
     * Get the monster.
     *
     * @return Monster
     */
    public function getMonster(): Monster {
        return $this->monster;
    }

    /**
     * Is the character dead?
     *
     * @return bool
     */
    public function isCharacterDead(): bool {
        return $this->currentCharacterHealth <= 0;
    }

    /**
     * Is the monster dead?
     *
     * @return bool
     */
    public function isMonsterDead(): bool {
        return $this->currentMonsterHealth <= 0;
    }

    /**
     * Did the adventure take too long?
     *
     * @return bool
     */
    public function tooLong(): bool {
        return $this->tookTooLong;
    }

    /**
     * Attack the enemy.
     *
     * This attack method mirrors the one on the client side.
     *
     * @param mixed $attacker | Character or Monster
     * @param mixed $defender | Character or Monster
     * @return void
     */
    public function attack($attacker, $defender) {
        if ($this->isCharacterDead() || $this->isMonsterDead()) {

            if ($this->isMonsterDead()) {
                $this->logInformation[] = [
                    'attacker'   => $defender->name,
                    'defender'   => $attacker->name,
                    'message'    => $attacker->name . ' has been defeated!',
                    'is_monster' => $defender instanceOf Character ? false : true
                ];
            }

            return;
        }

        if ($this->counter >= 10) {
            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'message'    => 'Floor took too long.',
                'is_monster' => $attacker instanceOf Character ? false : true
            ];

            $this->tookTooLong = true;

            $this->counter = 0;

            return;
        }

        if (!$this->canHit($attacker, $defender)) {
            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'message'    => $attacker->name . ' Missed!',
                'is_monster' => $attacker instanceOf Character ? false : true
            ];

            $this->counter += 1;

            return $this->attack($defender, $attacker);
        }

        if ($this->blockedAttack($defender, $attacker)) {
            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'message'    => $defender->name . ' blocked the attack!',
                'is_monster' => $attacker instanceOf Character ? false : true
            ];

            $this->counter += 1;

            return $this->attack($defender, $attacker);
        }

        $messages          = $this->completeAttack($attacker, $defender);

        $this->counter     = 0;

        $this->tookTooLong = false;

        $this->logInformation[] = [
            'attacker'   => $attacker->name,
            'defender'   => $defender->name,
            'messages'   => $messages,
            'is_monster' => $attacker instanceof Character ? false : true
        ];

        return $this->attack($defender, $attacker);
    }

    protected function canHit($attacker, $defender): bool {
        $accuracyBonus = $attacker->skills()->join('game_skills', function($join) {
                $join->on('game_skills.id', 'skills.game_skill_id')
                     ->where('game_skills.name', 'Accuracy');
        })->first()->skill_bonus;

        $dodgeBonus    = $defender->skills()->join('game_skills', function($join) {
                $join->on('game_skills.id', 'skills.game_skill_id')
                     ->where('game_skills.name', 'Dodge');
        })->first()->skill_bonus;

        if ($accuracyBonus < 1) {
            $accuracyBonus += 1;
        }

        if ($dodgeBonus < 1) {
            $dodgeBonus += 1;
        }

        $defenderDex      = $defender->dex;
        $defenderBaseStat = $defender->{$defender->damage_stat};

        $attackerDex      = $defender->dex;
        $attackerBaseStat = $defender->{$defender->damage_stat};

        if ($defender instanceof  Character) {
            $defenderDex      = $this->characterInformation->statMod('dex');
            $defenderBaseStat = $this->characterInformation->statMod($defender->damage_stat);
        }

        if ($attacker instanceof Character) {
            $attackerDex      = $this->characterInformation->statMod('dex');
            $attackerBaseStat = $this->characterInformation->statMod($defender->damage_stat);
        }

        $attack = $attackerBaseStat + round($attackerDex / 2) * $accuracyBonus;
        $dodge  = $defenderBaseStat + round($defenderDex / 2) * $dodgeBonus;

        return $attack > $dodge;
    }

    protected function blockedAttack($defender, $attacker): bool {
        $baseStat = $attacker->{$defender->damage_stat};
        $ac       = $defender->ac;

        if ($defender instanceof  Character) {
            $ac = $this->characterInformation->buildDefence();
        }

        if ($attacker instanceof Character) {
            $baseStat = $this->characterInformation->statMod($attacker->damage_stat);
        }

        return $ac > $baseStat;
    }

    protected function completeAttack($attacker, $defender): array {
        $messages = [];

        if ($attacker instanceof Character) {

            $characterAttack = $this->characterInformation->buildAttack();

            $this->currentMonsterHealth -= $characterAttack;

            if ($this->characterInformation->hasArtifacts()) {
                $messages[] = ['Your artifacts glow before the enemy!'];
            }

            if ($this->characterInformation->hasAffixes()) {
                $messages[] = ['The enchantments on your equipment lash out at the enemy!'];
            }

            if ($this->characterInformation->hasDamageSpells()) {
                $messages[] = ['Your spells burst forward towards the enemy!'];
            }

            $healFor = $this->characterInformation->buildHealFor();

            if ($healFor > 0) {
                $this->currentCharacterHealth = $healFor;

                $messages[] = ['Light floods your eyes as your wounds heal over.'];
            }

            $messages[] = [$this->character->name . ' hit for ' . number_format($characterAttack)];
        } else {
            $monsterAttack = $this->fetchMonsterAttack($attacker);

            $this->currentCharacterHealth -= $monsterAttack;

            $messages[] =  [$attacker->name . ' hit for ' . number_format($monsterAttack)];
        }

        return $messages;
    }

    protected function fetchMonsterAttack($attacker) {
        $attackRange = explode('-', $attacker->attack_range);

        return rand($attackRange[0], $attackRange[1]) + $attacker->{$attacker->damage_stat};
    }
}
