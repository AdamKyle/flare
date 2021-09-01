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
     * Used for celestial fights.
     *
     * @var int|null $attackTimes
     */
    private int|null $attackTimes = null;

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

        $this->characterInformation   = resolve(CharacterInformationBuilder::class)->setCharacter($character);
        $this->currentCharacterHealth = $this->characterInformation->buildHealth();

        $healthRange                  = explode('-', $this->monster->health_range);
        $this->currentMonsterHealth   = rand($healthRange[0], $healthRange[1]) + 10;
    }

    public function overrideMonsterHealth(int $monsterHealth): FightService {
        $this->currentMonsterHealth = $monsterHealth;

        return $this;
    }

    public function overrideCharacterHealth(int $characterHealth): FightService {
        $this->currentCharacterHealth = $characterHealth;

        return $this;
    }

    public function setAttackTimes(int $times): FightService {
        $this->attackTimes = $times;

        return $this;
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
     * Get current monster health.
     *
     * @return int
     */
    public function getRemainingMonsterHealth(): int {
        return $this->currentMonsterHealth;
    }

    /**
     * Get current character health.
     *
     * @return int
     */
    public function getRemainingCharacterHealth(): int {
        return $this->currentCharacterHealth;
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

        $messages = [];

        if ($attacker instanceof Character) {
            if (!is_null($this->attackTimes)) {
                if ($this->attackTimes <= 0) {
                    return;
                }

                $this->attackTimes -= 1;
            }
        }

        if ($this->isCharacterDead() || $this->isMonsterDead()) {

            if ($this->isMonsterDead()) {
                $this->logInformation[] = [
                    'attacker'   => $defender->name,
                    'defender'   => $attacker->name,
                    'messages'   => [[$attacker->name . ' has been defeated!']],
                    'is_monster' => $defender instanceOf Character ? false : true
                ];
            }

            return;
        }

        if ($this->counter >= 10) {
            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'messages'   => [['Floor took too long.']],
                'is_monster' => $attacker instanceOf Character ? false : true
            ];

            $this->tookTooLong = true;

            $this->counter = 0;

            return;
        }

        if (!$this->canHit($attacker, $defender)) {
            $messages   = array_merge($messages, $this->castSpell($attacker, $defender));
            $messages   = array_merge($messages, $this->useAtifacts($attacker, $defender));
            $messages   = array_merge($messages, $this->useRings($attacker));
            $messages[] = [$attacker->name . '(weapon) Missed!'];

            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'messages'   => $messages,
                'is_monster' => $attacker instanceOf Character ? false : true
            ];

            $this->counter += 1;

            return $this->attack($defender, $attacker);
        }

        if ($this->blockedAttack($defender, $attacker)) {
            $this->logInformation[] = [
                'attacker'   => $attacker->name,
                'defender'   => $defender->name,
                'messages'   => [[$defender->name . ' blocked the attack!']],
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

        $this->attack($defender, $attacker);
    }

    protected function canHit($attacker, $defender): bool {
        $accuracyBonus = $this->fetchAccuracyBonus($attacker);
        $dodgeBonus    = $this->fetchDodgeBonus($defender);

        if ($accuracyBonus > 1.0) {
            return true;
        }

        if ($dodgeBonus > 1.0) {
            return false;
        }

        if ($defender instanceof  Character) {
            $toHit = $this->toHitCalculation($attacker->dex, $this->characterInformation->statMod('dex'), $accuracyBonus, $dodgeBonus);
        }

        if ($attacker instanceof Character) {
            $toHit = $this->toHitCalculation($this->characterInformation->statMod($attacker->class->to_hit_stat), $defender->dex, $accuracyBonus, $dodgeBonus);
        }

        if ($toHit > 1.0) {
            return true;
        }

        $percent = floor((100 - $toHit));
        $needToHit = 100 - $percent;

        return rand(1, 100) > $needToHit;
    }

    /**
     * Fetch the accuracy bonus of the attacker.
     *
     * @param $attacker
     * @return float
     */
    protected function fetchAccuracyBonus($attacker): float {
        $accuracyBonus = $attacker->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                ->where('game_skills.name', 'Accuracy');
        })->first();

        if (is_null($accuracyBonus)) {
            $accuracyBonus = 0.0;
        } else {
            $accuracyBonus = $accuracyBonus->skill_bonus;
        }

        return $accuracyBonus;
    }

    /**
     * Fetch the dpdge bonus of the defender.
     *
     * @param $defender
     * @return float
     */
    protected function fetchDodgeBonus($defender): float {
        $dodgeBonus    = $defender->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                ->where('game_skills.name', 'Dodge');
        })->first();

        if (is_null($dodgeBonus)) {
            $dodgeBonus = 0.0;
        } else {
            $dodgeBonus = $dodgeBonus->skill_bonus;
        }

        return $dodgeBonus;
    }

    /**
     * Calculate the ToHit Percentage.
     *
     * This consists of dexterity, to hit stat, accuracy and the enemy dodge.
     *
     * @param int $toHit
     * @param int $dex
     * @param float $accuracy
     * @param float $dodge
     * @return float|int
     */
    protected function toHitCalculation(int $toHit, int $dex, float $accuracy, float $dodge) {
        $dex   = ($dex / 10000);
        $toHit = ($toHit + $toHit * $accuracy) / 100;

        return ($dex + $dex * $dodge) - $toHit;
    }

    /**
     * Was the attack blocked?
     *
     * @param $defender
     * @param $attacker
     * @return bool
     */
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

    /**
     * Casts your healing spells.
     *
     * Monsters do not have healing spells, but players do.
     *
     * @param $defender
     * @return array
     * @throws \Exception
     */
    protected function castHealingSpell($defender) {
        $messages = [];

        if ($defender instanceof Character) {
            $healFor = $this->characterInformation->buildHealFor();

            if ($healFor > 0 && $this->currentCharacterHealth !== $this->characterInformation->buildHealth()) {
                $this->currentCharacterHealth = $healFor;

                $messages[] = ['Light floods your eyes as your wounds heal over for: ' . $healFor];
            }
        }

        return $messages;
    }

    /**
     * Casts spells and deals with dealing damage.
     *
     * @param $attacker
     * @param $defender
     * @return array
     */
    protected function castSpell($attacker, $defender) {
        $messages = [];

        if ($attacker instanceof Character) {
            if ($this->characterInformation->hasDamageSpells()) {
                $messages[] = ['Your spells burst forward towards the enemy!'];
                $messages[] = $this->spellDamage($attacker, $defender);
            }
        }

        if ($attacker->can_cast) {
            $messages[] = ['The enemy begins to cast their spells!'];

            $messages[] = $this->spellDamage($attacker, $defender);
        }

        return $messages;
    }

    /**
     * Use rings.
     *
     * Only the character has rings.
     *
     * @param $attacker
     * @return array
     */
    protected function useRings($attacker): array {
        $messages = [];

        if ($attacker instanceof Character) {
            $ringDamage = $this->characterInformation->getTotalRingDamage();
            if ($ringDamage > 0) {
                $messages[]  = ['Your rings begin to shimmer in the presence of the enemy'];

                $this->currentMonsterHealth -= $ringDamage;

                $messages[] = ['Your rings lash out at the enemy for: ' . $ringDamage];
            }
        }

        return $messages;
    }

    /**
     * Deal artifact damage.
     *
     * @param $attacker
     * @param $defender
     * @return array
     */
    protected function useAtifacts($attacker, $defender) {
        $messages = [];

        if ($attacker instanceOf Character) {
            if ($this->characterInformation->hasArtifacts()) {
                $messages[] = ['Your artifacts glow before the enemy!'];
                $messages[] = $this->artifactDamage($attacker, $defender);
            }
        }

        if ($defender instanceOf Character) {
            if ($defender->can_use_artifacts) {
                $messages[] = ['The enemies artifacts begin to glow ...'];
                $messages[] = $this->artifactDamage($attacker, $defender);
            }
        }

        return $messages;
    }

    /**
     * Deals artifact damage either from player or monster.
     *
     * Damage can be annulled based on defenders' annulment percentage.
     *
     * @param $attacker
     * @param $defender
     * @return string[]|void
     */
    protected function artifactDamage($attacker, $defender) {
        if ($attacker instanceof Character) {
            $baseArtifactDamage = $this->characterInformation->getTotalArtifactDamage();
            $artifactDamage = $baseArtifactDamage - ($baseArtifactDamage * $defender->artifact_annulment);

            if ($artifactDamage > 0) {
                $health = ceil($this->currentMonsterHealth - $artifactDamage);

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentMonsterHealth = $health;

                if ($baseArtifactDamage !== $artifactDamage) {
                    return [
                        'Your artifacts hit the enemy for: ' . $artifactDamage . ' (Partially annulled)',
                    ];
                }

                return [
                    'Your artifacts hit the enemy for: ' . $artifactDamage,
                ];
            } else {
                return [
                    'Your artifacts were annulled ...'
                ];
            }
        }

        if ($defender instanceof Character){
            $baseArtifactDamage = rand(1, $defender->max_artifact_damage);
            $artifactDamage = $baseArtifactDamage - ($baseArtifactDamage * $this->characterInformation->getTotalAnnulment());

            if ($artifactDamage > 0) {
                $health = $this->currentCharacterHealth - $artifactDamage;

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentCharacterHealth = $health;

                if ($baseArtifactDamage !== $artifactDamage) {
                    return [
                        'The enemies artifacts lash out in intense energy doing: ' . $artifactDamage . ' (Partially annulled)',
                    ];
                }

                return [
                    'The enemies artifacts lash out in intense energy doing: ' . $artifactDamage,
                ];
            }else {
                return [
                    'The enemies artifacts were annulled ...'
                ];
            }
        }
    }

    /**
     * Deal spell damage to the enemy.
     *
     * Spell damage can be evaded based on the defenders spell evasion.
     *
     * @param $attacker
     * @param $defender
     * @return string[]|void
     */
    protected function spellDamage($attacker, $defender) {
        if ($attacker instanceof Character) {
            $spellDamage = $this->characterInformation->getTotalSpellDamage();
            $totalDamage = ceil($spellDamage - ($spellDamage * $defender->spell_evasion));

            if ($totalDamage > 0) {
                $health = $this->currentMonsterHealth - $totalDamage;

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentMonsterHealth = $health;

                if ($spellDamage !== $totalDamage) {
                    return [
                        'Your spells hit the enemy for: ' . $totalDamage . ' (Partially Annulled)',
                    ];
                }

                return [
                    'Your spells hit the enemy for: ' . $totalDamage,
                ];
            } else {
                return [
                    'Your spells have no effect ...'
                ];
            }
        }

        if ($defender instanceof Character){
            $spellDamage = rand(1, $attacker->max_spell_damage);
            $totalDamage = $spellDamage - ($spellDamage * $this->characterInformation->getTotalSpellEvasion());

            if ($totalDamage > 0) {
                $health = $this->currentCharacterHealth - $totalDamage;

                if ($health < 0) {
                    $health = 0;
                }

                $this->currentCharacterHealth = $health;

                if ($spellDamage !== $totalDamage) {
                    return [
                            'The enemies spells burst towards you, slamming into you for: ' . $totalDamage . ' (Partially Annulled)',
                    ];
                }

                return [
                    'The enemies spells burst towards you, slamming into you for: ' . $totalDamage,
                ];
            } else {
                return [
                    'The enemies spells have been nullified by you ...'
                ];
            }
        }
    }

    /**
     * Complete the attack on the defender.
     *
     * @param $attacker
     * @param $defender
     * @return array
     * @throws \Exception
     */
    protected function completeAttack($attacker, $defender): array {
        $messages = [];

        if ($attacker instanceof Character) {

            $characterAttack = $this->characterInformation->buildAttack();

            $this->currentMonsterHealth -= $characterAttack;

            if ($this->characterInformation->hasAffixes()) {
                $messages[] = ['The enchantments on your equipment lash out at the enemy!'];
            }

            $messages = array_merge($messages, $this->castSpell($attacker, $defender));
            $messages = array_merge($messages, $this->useAtifacts($attacker, $defender));
            $messages = array_merge($messages, $this->useRings($attacker));

            $messages[] = [$this->character->name . ' hit for (weapon): ' . number_format($characterAttack)];

        } else {
            $monsterAttack = $this->fetchMonsterAttack($attacker);

            $this->currentCharacterHealth -= $monsterAttack;

            $messages = array_merge($messages, $this->castSpell($attacker, $defender));
            $messages = array_merge($messages, $this->useAtifacts($attacker, $defender));

            $messages[] =  [$attacker->name . ' hit for ' . number_format($monsterAttack)];

            if ($this->currentCharacterHealth > 0 && $this->currentCharacterHealth < $this->characterInformation->buildHealth()) {
                $messages = array_merge($messages, $this->castHealingSpell($defender));
            } else if ($this->currentCharacterHealth <= 0) {
                $resChance = $this->characterInformation->fetchResurrectionChance();
                $dc        = 100 - 100 * $resChance;
                $chRoll    = rand(1, 100);

                if ($chRoll > $dc) {
                    $this->currentCharacterHealth = 0;

                    $messages = array_merge($messages, $this->castHealingSpell($defender));
                }
            }
        }

        return $messages;
    }

    /**
     * Fetches the monsters attack damage from their attack range.
     *
     * @param $attacker
     * @return int
     */
    protected function fetchMonsterAttack($attacker) {
        $attackRange = explode('-', $attacker->attack_range);

        return rand($attackRange[0], $attackRange[1]) + $attacker->{$attacker->damage_stat};
    }
}
