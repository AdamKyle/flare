<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Events\UpdateSkillEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class DisenchantService {

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
     * @var SkillCheckService $skillCheckService
     */
    private SkillCheckService $skillCheckService;

    public function __construct(SkillCheckService $skillCheckService) {
        $this->skillCheckService = $skillCheckService;
    }

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
        if ($this->character->gold_dust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $slot->delete();

            $affixData = resolve(EnchantingService::class)->fetchAffixes($this->character->refresh());

            event(new UpdateCharacterEnchantingList(
                $this->character->user,
                $affixData['affixes'],
                $affixData['character_inventory'],
            ));

            return;
        }

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck       = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        if ($characterRoll > $dcCheck) {
            $goldDust = $this->updateGoldDust($this->character);

            ServerMessageHandler::handleMessage($this->character->user, 'disenchanted', number_format($goldDust));

            event(new UpdateSkillEvent($this->disenchantingSkill));

        } else {
            $this->updateGoldDust($this->character, true);

            ServerMessageHandler::handleMessage($this->character->user, 'failed_to_disenchant');
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

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck       = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        $characterCurrentGoldDust = $this->character->gold_dust;

        $canDisenchant = $characterRoll > $dcCheck;

        if ($characterCurrentGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST && $canDisenchant) {
            event(new UpdateSkillEvent($this->disenchantingSkill));

            return;
        }

        if ($canDisenchant) {
            $goldDust = $this->updateGoldDust($this->character);

            ServerMessageHandler::handleMessage($this->character->user, 'disenchanted', number_format($goldDust));

            event(new UpdateSkillEvent($this->disenchantingSkill));

        } else {
            $this->updateGoldDust($this->character, true);

            ServerMessageHandler::handleMessage($this->character->user, 'failed_to_disenchant');
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

        $goldDust = !$failedCheck ? rand(2, 1150) : 1;

        $goldDust = $goldDust + $goldDust *  $this->disenchantingSkill->bonus;

        $characterTotalGoldDust = $character->gold_dust + $goldDust;
        if (!$failedCheck) {

            $dc   = 500 - 500 * 0.10;
            $roll = $this->fetchDCRoll();

            if ($roll >= $dc) {
                $characterTotalGoldDust = $characterTotalGoldDust + $characterTotalGoldDust * 0.05;

                if ($characterTotalGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
                    $characterTotalGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;

                    event(new ServerMessageEvent($character->user, 'Gold Dust Rush! You gained 5% interest on your total gold dust. You are now capped!'));
                } else {
                    event(new ServerMessageEvent($character->user, 'Gold Dust Rush! You gained 5% interest on your total gold dust. Your new total is: ' . number_format($characterTotalGoldDust)));
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
        return rand(1, 500);
    }
}
