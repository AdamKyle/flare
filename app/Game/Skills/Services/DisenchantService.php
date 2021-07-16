<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Game\Skills\Services\Traits\SkillCheck;

class DisenchantService {

    use SkillCheck;

    /**
     * @var int $goldDust
     */
    private $goldDust = 0;

    /**
     * Disenchant the item.
     *
     * @param Character $character
     * @param InventorySlot $slot
     */
    public function disenchantWithSkill(Character $character, InventorySlot $slot) {
        $disenchantSkill = $character->skills->filter(function($skill) {
           return $skill->type()->isDisenchanting();
        })->first();

        if (!is_null($disenchantSkill)) {

            $characterRoll = $this->characterRoll($disenchantSkill);
            $dcCheck       = $this->getDCCheck($disenchantSkill, 0);

            if ($characterRoll > $dcCheck) {
                $goldDust = $this->updateGoldDust($character);

                event(new ServerMessageEvent($character->user, 'disenchanted', $goldDust));
                event(new UpdateSkillEvent($disenchantSkill));
            } else {
                $this->updateGoldDust($character, true);

                event(new ServerMessageEvent($character->user, 'failed-to-disenchant'));
            }
        }

        $slot->delete();
    }

    /**
     * Disenchant the item, with out giving skill xp.
     *
     * @param Character $character
     * @param InventorySlot $slot
     */
    public function disenchantWithOutSkill(Character $character, InventorySlot $slot) {
        $goldDust = $this->updateGoldDust($character);

        event(new ServerMessageEvent($character->user, 'disenchanted-with-out-skill', $goldDust));

        $slot->delete();
    }

    /**
     * Return the total gold dust.
     *
     * @return int
     */
    public function getGoldDust(): int {
        return $this->goldDust;
    }

    /**
     * Update the characters gold dust.
     *
     * @param Character $character
     * @param bool $failedCheck
     * @return int
     */
    protected function updateGoldDust(Character $character, bool $failedCheck = false): int {
        $goldDust = !$failedCheck ? rand(1, 150) : 1;

        $character->update([
            'gold_dust' => $character->gold_dust + $goldDust
        ]);

        $this->goldDust += $goldDust;

        event(new UpdateTopBarEvent($character->refresh()));

        return $goldDust;
    }
}
