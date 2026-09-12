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

    private EnchantingService $enchantingService;

    private Character $character;

    private Skill $disenchantingSkill;

    private ?InventorySlot $questSlot = null;

    public function __construct(
        private readonly SkillCheckService $skillCheckService,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
    ) {}

    /**
     * Set up the service for the given character, resolving their Disenchanting skill and Gold Dust Rush quest slot.
     *
     * @param Character $character The character disenchanting an item.
     * @return DisenchantService The configured service instance.
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
     * @param InventorySlot|SetSlot $slot The slot holding the item being disenchanted.
     * @param bool $doNotSendResponse Whether to suppress the player-facing response message.
     * @return array The disenchant response payload.
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
     * @param InventorySlot|SetSlot $slot The slot holding the item being disenchanted.
     * @return void This method does not return a value.
     */
    public function disenchantWithSkill(InventorySlot|SetSlot $slot): void
    {

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        $disenchanted = $characterRoll >= $dcCheck;

        if ($this->character->gold_dust >= CurrencyLimit::MAX_GOLD_DUST) {

            $affixData = resolve(EnchantingService::class)->fetchAffixes($this->character->refresh());

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

        $affixData = resolve(EnchantingService::class)->fetchAffixes($this->character->refresh());

        event(new UpdateCharacterEnchantingList(
            $this->character->user,
            $affixData['affixes'],
            $affixData['character_inventory'],
        ));

        event(new UpdateCharacterInventoryCountEvent($this->character));
    }

    /**
     * Disenchant a Batch Crafting produced item that was never placed into an Inventory slot.
     *
     * Mirrors disenchantWithSkill()'s roll and Gold Dust reward without any slot to update or
     * delete, since a Batch Crafting disenchant target never entered the character's Inventory.
     *
     * @return void This method does not return a value.
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
     * @return void This method does not return a value.
     */
    public function disenchantItemWithSkill(): void
    {

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        $characterCurrentGoldDust = $this->character->gold_dust;

        $canDisenchant = $characterRoll > $dcCheck;

        if ($characterCurrentGoldDust >= CurrencyLimit::MAX_GOLD_DUST && $canDisenchant) {
            event(new UpdateSkillEvent($this->disenchantingSkill));

            event(new UpdateCharacterInventoryCountEvent($this->character));

            ServerMessageHandler::sendBasicMessage($this->character->user, 'Disenchanted item but got no gold dust as you are capped. Maybe you want to auto sell it (can be enabled in your settings, profile icon -> settings, scroll down to Auto Disenchant)?');

            return;
        }

        if ($canDisenchant) {
            $goldDust = $this->updateGoldDust($this->character);

            ServerMessageHandler::handleMessage($this->character->user, CraftingMessageTypes::DISENCHANTED, number_format($goldDust));

            event(new UpdateSkillEvent($this->disenchantingSkill));

            event(new UpdateCharacterInventoryCountEvent($this->character));
        } else {
            $this->updateGoldDust($this->character, true);

            ServerMessageHandler::handleMessage($this->character->user, CraftingMessageTypes::FAILED_TO_DISENCHANT);
        }
    }

    /**
     * Roll and award the character's Gold Dust for a disenchant outcome, applying the Disenchanting skill bonus.
     *
     * @param Character $character The character being awarded Gold Dust.
     * @param bool $failedCheck Whether the disenchant skill check failed.
     * @param bool $canRollGoldDustRush Whether this award may also roll the Gold Dust Rush bonus.
     * @return int The Gold Dust amount awarded before the Gold Dust Rush bonus.
     */
    public function updateGoldDust(Character $character, bool $failedCheck = false, bool $canRollGoldDustRush = true): int
    {

        $goldDust = ! $failedCheck ? $this->fetchGoldDustAmount() : 1;

        $goldDust = (int) ($goldDust + $goldDust * $this->disenchantingSkill->bonus);

        $characterTotalGoldDust = $character->gold_dust + $goldDust;
        $goldDustRushAwarded = false;

        if (! $failedCheck) {
            if ($canRollGoldDustRush && $this->canAwardGoldDustRush() && $this->fetchDCRoll() === 100) {
                $characterTotalGoldDust += (int) floor($goldDust * 0.05);
                $goldDustRushAwarded = true;
            }
        }

        if ($characterTotalGoldDust >= CurrencyLimit::MAX_GOLD_DUST) {
            $characterTotalGoldDust = CurrencyLimit::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $characterTotalGoldDust,
        ]);

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));

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
     * @param Character $character The character being awarded the bonus.
     * @param int $goldDustGain The base Gold Dust gained from the disenchant.
     * @return void This method does not return a value.
     */
    public function applyGoldDustRushBonus(Character $character, int $goldDustGain): void
    {
        if ($goldDustGain <= 0 || ! $this->canAwardGoldDustRush()) {
            return;
        }

        if ($this->fetchDCRoll() !== 100) {
            return;
        }

        $characterTotalGoldDust = $character->gold_dust + (int) floor($goldDustGain * 0.05);

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
     * @return bool True when the quest effect is active for this disenchant.
     */
    private function canAwardGoldDustRush(): bool
    {
        return ! is_null($this->questSlot);
    }

    /**
     * Roll the random Gold Dust Rush bonus amount.
     *
     * @return int The rolled Gold Dust bonus amount.
     */
    private function fetchGoldDustAmount(): int
    {
        return $this->randomNumberGenerator->numberBetween(2, 1150);
    }

    /**
     * Roll the Gold Dust Rush one-in-a-hundred chance check.
     *
     * @return int 100 when the roll passes, otherwise 99.
     */
    private function fetchDCRoll(): int
    {
        return $this->chanceCalculator->passesOneIn(100) ? 100 : 99;
    }
}
