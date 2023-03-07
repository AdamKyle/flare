<?php

namespace App\Game\Skills\Services;

use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Events\UpdateSkillEvent;
use App\Game\Core\Traits\MercenaryBonus;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Services\Traits\SkillCheck;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class DisenchantService {

    use SkillCheck, MercenaryBonus;

    /**
     * @var EnchantingService $enchantingService
     */
    private EnchantingService $enchantingService;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @var Skill $disenchantingSkill
     */
    private Skill $disenchantingSkill;

    /**
     * @var InventorySlot|null $questSlot
     */
    private ?InventorySlot $questSlot = null;

    /**
     * Set up the service.
     *
     * @param Character $character
     * @return DisenchantService
     */
    public function setUp(Character $character): DisenchantService {
        $this->character = $character;

        $this->disenchantingSkill = $character->skills->filter(function($skill) {
            return $skill->type()->isDisenchanting();
        })->first();;

        $this->questSlot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::GOLD_DUST_RUSH;
        })->first();

        return $this;
    }

    /**
     * Disenchant the item.
     *
     * @param InventorySlot $slot
     * @return void
     */
    public function disenchantWithSkill(InventorySlot $slot): void {

        $characterRoll = $this->characterRoll($this->disenchantingSkill);
        $dcCheck       = $this->getDCCheck($this->disenchantingSkill);

        if ($characterRoll > $dcCheck) {
            $goldDust = $this->updateGoldDust($this->character);

            ServerMessageHandler::handleMessage($this->character->user, 'disenchanted', number_format($goldDust));

            event(new UpdateSkillEvent($this->disenchantingSkill));

        } else {
            $this->updateGoldDust($this->character, true);

            ServerMessageHandler::handleMessage($this->character->user, 'failed-to-disenchant');
        }

        $slot->delete();

        $affixData = resolve(EnchantingService::class)->fetchAffixes($this->character->refresh());

        event(new UpdateCharacterEnchantingList(
            $this->character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));
    }

    /**
     * Disenchant item with skill.
     *
     * @return void
     */
    public function disenchantItemWithSkill(): void {
        $characterRoll = $this->characterRoll($this->disenchantingSkill);
        $dcCheck       = $this->getDCCheck($this->disenchantingSkill);

        if ($characterRoll > $dcCheck) {
            $goldDust = $this->updateGoldDust($this->character);

            ServerMessageHandler::handleMessage($this->character->user, 'disenchanted', number_format($goldDust));

            event(new UpdateSkillEvent($this->disenchantingSkill));

        } else {
            $this->updateGoldDust($this->character, true);

            ServerMessageHandler::handleMessage($this->character->user, 'failed-to-disenchant');
        }
    }

    /**
     * Update the characters gold dust.
     *
     * @param Character $character
     * @param bool $failedCheck
     * @return int
     */
    protected function updateGoldDust(Character $character, bool $failedCheck = false): int {
        $goldDust = !$failedCheck ? rand(2, 15) : 1;

        if (!$failedCheck) {
            $goldDust = $goldDust + $goldDust * $this->disenchantingSkill->bonus;
        }

        $goldDust = $goldDust + $goldDust * $this->getGoldDustBonus($character);

        $characterTotalGoldDust = $character->gold_dust + $goldDust;

        if (!is_null($this->questSlot) && !$failedCheck) {
            $dc   = 1000 - 1000 * 0.02;
            $roll = $this->fetchDCRoll();

            if ($roll > $dc) {;

                $characterTotalGoldDust = $characterTotalGoldDust + $characterTotalGoldDust * 0.05;

                if ($characterTotalGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
                    $characterTotalGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
                    event(new ServerMessageEvent($character->user, 'Gold Dust Rush! You gained 5% interest on your total gold dust. You are now capped!'));
                } else {
                    event(new ServerMessageEvent($character->user, 'Gold Dust Rush! You gained 5% interest on your total gold dust. Your new total is: ' . $characterTotalGoldDust));
                }
            }
        }

        if ($characterTotalGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $characterTotalGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $characterTotalGoldDust
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        return $goldDust;
    }

    /**
     * fetch the DC roll.
     *
     * @return int
     */
    protected function fetchDCRoll(): int {
        return rand(1, 1000);
    }
}
