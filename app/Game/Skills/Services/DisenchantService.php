<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Flare\Models\Skill;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Events\UpdateSkillEvent;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class DisenchantService
{
    use ResponseBuilder;

    private Character $character;

    private Skill $disenchantingSkill;

    private ?InventorySlot $questSlot = null;

    /**
     * @param SkillCheckService $skillCheckService
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param ChanceCalculator $chanceCalculator
     * @param EnchantingAffixService $enchantingAffixService
     */
    public function __construct(
        private readonly SkillCheckService $skillCheckService,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
        private readonly EnchantingAffixService $enchantingAffixService,
    ) {}

    /**
     * Set up the service for the given character, resolving their Disenchanting skill and Gold Dust Rush quest slot.
     *
     * @param Character $character
     * @return DisenchantService
     */
    public function setUp(Character $character): DisenchantService
    {
        $this->character = $character;

        $this->disenchantingSkill = $character->skills->filter(function ($skill) {
            return $skill->type()->isDisenchanting();
        })->first();

        $this->questSlot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectType::GOLD_DUST_RUSH->value;
        })->first();

        return $this;
    }

    /**
     * Disenchant the item held in an Inventory or Set slot and return the response payload.
     *
     * @param InventorySlot|SetSlot $slot
     * @param bool $doNotSendResponse
     * @return array
     */
    public function disenchantItem(InventorySlot|SetSlot $slot, bool $doNotSendResponse = false): array
    {
        $itemName = $slot->item->affix_name;

        $this->disenchantWithSkill($slot);

        $character = $this->character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        if ($doNotSendResponse) {
            return $this->successResult();
        }

        return $this->successResult([
            'message' => 'Disenchanted item '.$itemName.' Check server message tab for Gold Dust output.',
        ]);
    }

    /**
     * Roll the Disenchanting skill check, award or deny Gold Dust, and delete the disenchanted slot.
     *
     * @param InventorySlot|SetSlot $slot
     * @return void
     */
    public function disenchantWithSkill(InventorySlot|SetSlot $slot): void
    {

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        $disenchanted = $characterRoll >= $dcCheck;

        if ($this->character->gold_dust >= CurrencyLimit::MAX_GOLD_DUST) {

            $affixData = $this->enchantingAffixService->fetchAffixes($this->character->refresh());

            if ($disenchanted) {
                event(new UpdateSkillEvent($this->disenchantingSkill));
            }

            event(new UpdateCharacterEnchantingList(
                $this->character->user,
                $affixData['affixes'],
                $affixData['character_inventory'],
            ));

            $message = 'You are maxed on gold dust and '.(
                $disenchanted ? ' you still managed to disenchant the item: '.$slot->item->affix_name :
                'you failed to disenchant the item: '.$slot->item->affix_name
            );

            ServerMessageHandler::sendBasicMessage($this->character->user, $message);

            $slot->delete();

            event(new UpdateCharacterInventoryCountEvent($this->character));

            return;
        }

        if ($disenchanted) {
            $goldDust = $this->updateGoldDust($this->character);

            ServerMessageHandler::handleMessage($this->character->user, CraftingMessageTypes::DISENCHANTED, number_format($goldDust));

            event(new UpdateSkillEvent($this->disenchantingSkill));
        } else {
            $this->updateGoldDust($this->character, true);

            ServerMessageHandler::handleMessage($this->character->user, CraftingMessageTypes::FAILED_TO_DISENCHANT);
        }

        $slot->delete();

        $affixData = $this->enchantingAffixService->fetchAffixes($this->character->refresh());

        event(new UpdateCharacterEnchantingList(
            $this->character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        event(new UpdateCharacterInventoryCountEvent($this->character));
    }

    /**
     * Disenchant a Batch Crafting produced item that was never placed into an Inventory slot, mirroring disenchantWithSkill()'s roll and Gold Dust reward without any slot to update or delete.
     *
     * @return void
     */
    public function disenchantBatchCraftedItem(): void
    {
        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        $disenchanted = $characterRoll >= $dcCheck;

        if ($disenchanted) {
            $goldDust = $this->updateGoldDust($this->character);

            ServerMessageHandler::handleMessage($this->character->user, CraftingMessageTypes::DISENCHANTED, number_format($goldDust));

            event(new UpdateSkillEvent($this->disenchantingSkill));

            return;
        }

        $this->updateGoldDust($this->character, true);

        ServerMessageHandler::handleMessage($this->character->user, CraftingMessageTypes::FAILED_TO_DISENCHANT);
    }

    /**
     * Roll the Disenchanting skill check and award or deny Gold Dust, sending the maxed-currency message when capped.
     *
     * @return void
     */
    public function disenchantItemWithSkill(): void
    {

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        $characterCurrentGoldDust = $this->character->gold_dust;

        $canDisenchant = $characterRoll > $dcCheck;

        if ($characterCurrentGoldDust >= CurrencyLimit::MAX_GOLD_DUST && $canDisenchant) {
            event(new UpdateSkillEvent($this->disenchantingSkill));

            ServerMessageHandler::sendBasicMessage($this->character->user, 'Disenchanted item but got no gold dust as you are capped. Maybe you want to auto sell it (can be enabled in your settings, profile icon -> settings, scroll down to Auto Disenchant)?');

            return;
        }

        if ($canDisenchant) {
            $goldDust = $this->persistGoldDust($this->character);

            ServerMessageHandler::handleMessage($this->character->user, CraftingMessageTypes::DISENCHANTED, number_format($goldDust));

            event(new UpdateSkillEvent($this->disenchantingSkill));
        } else {
            $this->persistGoldDust($this->character, true);

            ServerMessageHandler::handleMessage($this->character->user, CraftingMessageTypes::FAILED_TO_DISENCHANT);
        }
    }

    /**
     * Roll and award the character's Gold Dust for a disenchant outcome, dispatching a Character-details refresh.
     *
     * @param Character $character
     * @param bool $failedCheck
     * @param bool $canRollGoldDustRush
     * @return int
     */
    public function updateGoldDust(Character $character, bool $failedCheck = false, bool $canRollGoldDustRush = true): int
    {
        $goldDust = $this->persistGoldDust($character, $failedCheck, $canRollGoldDustRush);

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));

        return $goldDust;
    }

    /**
     * Persist one Gold Dust disenchant result without dispatching a Character-details refresh.
     *
     * @param Character $character
     * @param bool $failedCheck
     * @param bool $canRollGoldDustRush
     * @return int
     */
    private function persistGoldDust(Character $character, bool $failedCheck = false, bool $canRollGoldDustRush = true): int
    {
        $goldDust = ! $failedCheck ? $this->fetchGoldDustAmount() : 1;

        $goldDustBonusMultiplier = $this->disenchantingSkill->bonus ?? 0;
        $goldDust += $goldDust * $goldDustBonusMultiplier;

        $characterTotalGoldDust = $character->gold_dust + $goldDust;
        $goldDustRushAwarded = false;

        if (! $failedCheck) {
            if ($canRollGoldDustRush && $this->canAwardGoldDustRush() && $this->fetchDCRoll() === 100) {
                $characterTotalGoldDust += intdiv($goldDust, 20);
                $goldDustRushAwarded = true;
            }
        }

        if ($characterTotalGoldDust >= CurrencyLimit::MAX_GOLD_DUST) {
            $characterTotalGoldDust = CurrencyLimit::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $characterTotalGoldDust,
        ]);

        if ($goldDustRushAwarded) {
            if ($characterTotalGoldDust >= CurrencyLimit::MAX_GOLD_DUST) {
                event(new ServerMessageEvent($character->user, 'Gold Dust Rush! You gained 5% bonus gold dust from disenchanting. You are now capped!'));
            } else {
                event(new ServerMessageEvent($character->user, 'Gold Dust Rush! You gained 5% bonus gold dust from disenchanting. Your new total is: '.number_format($characterTotalGoldDust)));
            }
        }

        return $goldDust;
    }

    /**
     * Award the Gold Dust Rush quest-effect bonus on top of a successful disenchant, when eligible.
     *
     * @param Character $character
     * @param int $goldDustGain
     * @return void
     */
    public function applyGoldDustRushBonus(Character $character, int $goldDustGain): void
    {
        if ($goldDustGain <= 0 || ! $this->canAwardGoldDustRush()) {
            return;
        }

        if ($this->fetchDCRoll() !== 100) {
            return;
        }

        $characterTotalGoldDust = $character->gold_dust + intdiv($goldDustGain, 20);

        if ($characterTotalGoldDust >= CurrencyLimit::MAX_GOLD_DUST) {
            $characterTotalGoldDust = CurrencyLimit::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $characterTotalGoldDust,
        ]);

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));
    }

    /**
     * Determine whether the current disenchant has the Gold Dust Rush quest effect active.
     *
     * @return bool
     */
    private function canAwardGoldDustRush(): bool
    {
        return ! is_null($this->questSlot);
    }

    /**
     * Roll the random Gold Dust Rush bonus amount.
     *
     * @return int
     */
    private function fetchGoldDustAmount(): int
    {
        return $this->randomNumberGenerator->numberBetween(2, 1150);
    }

    /**
     * Roll the Gold Dust Rush one-in-a-hundred chance check, returning 100 when the roll passes, otherwise 99.
     *
     * @return int
     */
    private function fetchDCRoll(): int
    {
        return $this->chanceCalculator->passesOneIn(100) ? 100 : 99;
    }
}
