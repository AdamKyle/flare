<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Item;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Messages\Events\ServerMessageEvent as MessageEvent;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Services\Traits\SkillCheck;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DisenchantService {

    use SkillCheck;

    /**
     * @var int $goldDust
     */
    private $goldDust = 0;

    private $enchantingService;

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
                $goldDust = $this->updateGoldDust($character, false, $disenchantSkill);

                event(new ServerMessageEvent($character->user, 'disenchanted', $goldDust));
                event(new UpdateSkillEvent($disenchantSkill));

            } else {
                $this->updateGoldDust($character, true);

                event(new ServerMessageEvent($character->user, 'failed-to-disenchant'));
            }
        }

        $slot->delete();

        $affixData = resolve(EnchantingService::class)->fetchAffixes($character->refresh());

        event(new UpdateCharacterEnchantingList(
            $character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));
    }

    public function disenchantItemWithSkill(Character $character) {
        $disenchantSkill = $character->skills->filter(function($skill) {
            return $skill->type()->isDisenchanting();
        })->first();

        if (!is_null($disenchantSkill)) {
            $characterRoll = $this->characterRoll($disenchantSkill);
            $dcCheck       = $this->getDCCheck($disenchantSkill, 0);

            if ($characterRoll > $dcCheck) {
                $goldDust = $this->updateGoldDust($character, false, $disenchantSkill);

                event(new ServerMessageEvent($character->user, 'disenchanted-adventure', $goldDust));
                event(new UpdateSkillEvent($disenchantSkill));

            } else {
                $this->updateGoldDust($character, true);

                event(new ServerMessageEvent($character->user, 'failed-to-disenchant'));
            }
        }
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
    protected function updateGoldDust(Character $character, bool $failedCheck = false, Skill $skill = null): int {
        $goldDust = !$failedCheck ? rand(2, 15) : 1;

        if (!$failedCheck && !is_null($skill)) {
            $goldDust = $goldDust + $goldDust * $skill->bonus;
        }

        $questSlot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::GOLD_DUST_RUSH;
        })->first();

        $characterTotalGoldDust = $character->gold_dust + $goldDust;

        if (!is_null($questSlot) && !$failedCheck) {
            $dc   = 1000 - 1000 * 0.02;
            $roll = $this->fetchDCRoll();

            if ($roll > $dc) {;

                $characterTotalGoldDust = $characterTotalGoldDust + $characterTotalGoldDust * 0.05;

                event(new MessageEvent($character->user, 'Gold Dust Rush! You gained 5% interest on your total gold dust. Your new total is: ' . $characterTotalGoldDust));
            }
        }

        if ($characterTotalGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $characterTotalGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $characterTotalGoldDust
        ]);

        $this->goldDust += $goldDust;

        event(new UpdateTopBarEvent($character->refresh()));

        return $goldDust;
    }

    protected function fetchDCRoll(): int {
        return rand(1, 1000);
    }
}
