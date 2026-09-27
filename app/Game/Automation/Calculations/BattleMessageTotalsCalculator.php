<?php

namespace App\Game\Automation\Calculations;

use RuntimeException;

class BattleMessageTotalsCalculator
{
    /**
     * Sum weapon, spell, healing, and blocked totals from the fight's battle messages.
     *
     * @param array $fightData
     * @return array
     *
     * @throws RuntimeException
     */
    public function totals(array $fightData): array
    {
        $totals = [
            'weapon_damage' => 0,
            'spell_damage' => 0,
            'healing_done' => 0,
            'damage_blocked' => 0,
        ];

        foreach ($fightData['attack_messages'] ?? [] as $messageData) {
            if (! is_array($messageData)) {
                continue;
            }

            $message = $messageData['message'] ?? null;

            if (! is_string($message)) {
                continue;
            }

            $totals['weapon_damage'] += $this->extractWeaponDamageFromMessage($message);
            $totals['spell_damage'] += $this->extractSpellDamageFromMessage($message);
            $totals['healing_done'] += $this->extractHealingFromMessage($message);
            $totals['damage_blocked'] += $this->extractBlockedFromMessage($message);
        }

        return $totals;
    }

    /**
     * Extract weapon damage from a battle message, if present.
     *
     * @param string $message
     * @return int
     */
    private function extractWeaponDamageFromMessage(string $message): int
    {
        $patterns = [
            '/Your weapon hits .+ for: ([0-9,]+)/',
            '/You hit for \(weapon - double attack\) ([0-9,]+)/',
            '/You hit for \((?:Gunslingers Assassination!|Book Binders Fear|Hammer|Arcane Alchemist Ravenous Dream)\):? ([0-9,]+)/',
            '/You slash, you thrash, you bash and you crash your way through! \(You dealt: ([0-9,]+)\)/',
            '/You strike the enemy in an ambush doing: ([0-9,]+) damage!/',
            '/Your class special: .+ fires off and you do: ([0-9,]+) damage to the enemy!/',
        ];

        return $this->extractMessageTotal($message, $patterns);
    }

    /**
     * Extract spell damage from a battle message, if present.
     *
     * @param string $message
     * @return int
     */
    private function extractSpellDamageFromMessage(string $message): int
    {
        $patterns = [
            '/Your damage spell\(s\) hits .+ for: ([0-9,]+)/',
            '/Your spell\(s\) hits for: ([0-9,]+)/',
            '/You hit for \(Arcane Alchemist Ravenous Dream\): ([0-9,]+)/',
        ];

        return $this->extractMessageTotal($message, $patterns);
    }

    /**
     * Extract healing done from a battle message, if present.
     *
     * @param string $message
     * @return int
     */
    private function extractHealingFromMessage(string $message): int
    {
        $patterns = [
            '/gives you life: ([0-9,]+)/',
            '/You healed for: ([0-9,]+)/',
            '/You heal for: ([0-9,]+)/',
        ];

        return $this->extractMessageTotal($message, $patterns);
    }

    /**
     * Extract damage blocked from a battle message, if present.
     *
     * @param string $message
     * @return int
     */
    private function extractBlockedFromMessage(string $message): int
    {
        $patterns = [
            '/You reduced the incoming \(Physical\) damage with your armour by: ([0-9,]+)/',
            '/You block: ([0-9,]+) of the enemies special attack damage!/',
        ];

        return $this->extractMessageTotal($message, $patterns);
    }

    /**
     * Return the first numeric match found in the message for the given regex patterns, or zero
     * when no pattern matches. A pattern that matches but captures a non-numeric value indicates
     * corrupted battle message data rather than an absent reward, so that case is not treated as zero.
     *
     * @param string $message
     * @param array $patterns
     * @return int
     *
     * @throws RuntimeException
     */
    private function extractMessageTotal(string $message, array $patterns): int
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches) === 1) {
                $total = filter_var(str_replace(',', '', $matches[1]), FILTER_VALIDATE_INT);

                if ($total === false) {
                    throw new RuntimeException(
                        'Battle message matched pattern "'.$pattern.'" with a non-numeric captured value: '.$matches[1],
                    );
                }

                return $total;
            }
        }

        return 0;
    }
}
