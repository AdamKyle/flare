<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Skill;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
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

    private SkillCheckService $skillCheckService;

    private CharacterInventoryService $characterInventoryService;

    private ?InventorySlot $questSlot = null;

    public function __construct(SkillCheckService $skillCheckService, CharacterInventoryService $characterInventoryService)
    {
        $this->skillCheckService = $skillCheckService;

        $this->characterInventoryService = $characterInventoryService;
    }

    /**
     * Set up the service.
     */
    public function setUp(Character $character): DisenchantService
    {
        $this->character = $character;

        $this->disenchantingSkill = $character->skills->filter(function ($skill) {
            return $skill->type()->isDisenchanting();
        })->first();

        $this->questSlot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::GOLD_DUST_RUSH;
        })->first();

        return $this;
    }

    /**
     * Disenchant the item.
     */
    public function disenchantItem(InventorySlot $slot, bool $doNotSendResponse = false): array
    {
        $itemName = $slot->item->affix_name;

        $this->disenchantWithSkill($slot);

        $character = $this->character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        if ($doNotSendResponse) {
            return $this->successResult();
        }

        return $this->successResult([
            'message' => 'Disenchanted item ' . $itemName . ' Check server message tab for Gold Dust output.',
        ]);
    }

    /**
     * Disenchant the item.
     */
    public function disenchantWithSkill(InventorySlot $slot): void
    {

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        $disenchanted = $characterRoll >= $dcCheck;

        if ($this->character->gold_dust >= MaxCurrenciesValue::MAX_GOLD_DUST) {

            $affixData = resolve(EnchantingService::class)->fetchAffixes($this->character->refresh());

            if ($disenchanted) {
                event(new UpdateSkillEvent($this->disenchantingSkill));
            }

            event(new UpdateCharacterEnchantingList(
                $this->character->user,
                $affixData['affixes'],
                $affixData['character_inventory'],
            ));

            $message = 'You are maxed on gold dust and ' . (
                $disenchanted ? ' you still managed to disenchant the item: ' . $slot->item->affix_name :
                'you failed to disenchant the item: ' . $slot->item->affix_name
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
     * Disenchant item with skill.
     */
    public function disenchantItemWithSkill(): void
    {

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

        $characterCurrentGoldDust = $this->character->gold_dust;

        $canDisenchant = $characterRoll > $dcCheck;

        if ($characterCurrentGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST && $canDisenchant) {
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
     * Update the characters gold dust.
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

        if ($characterTotalGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $characterTotalGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $characterTotalGoldDust,
        ]);

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));

        if ($goldDustRushAwarded) {
            if ($characterTotalGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
                event(new ServerMessageEvent($character->user, 'Gold Dust Rush! You gained 5% bonus gold dust from disenchanting. You are now capped!'));
            } else {
                event(new ServerMessageEvent($character->user, 'Gold Dust Rush! You gained 5% bonus gold dust from disenchanting. Your new total is: ' . number_format($characterTotalGoldDust)));
            }
        }

        return $goldDust;
    }

    public function applyGoldDustRushBonus(Character $character, int $goldDustGain): void
    {
        if ($goldDustGain <= 0 || ! $this->canAwardGoldDustRush()) {
            return;
        }

        if ($this->fetchDCRoll() !== 100) {
            return;
        }

        $characterTotalGoldDust = $character->gold_dust + (int) floor($goldDustGain * 0.05);

        if ($characterTotalGoldDust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $characterTotalGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        $character->update([
            'gold_dust' => $characterTotalGoldDust,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));
    }

    private function canAwardGoldDustRush(): bool
    {
        return ! is_null($this->questSlot);
    }

    protected function fetchGoldDustAmount(): int
    {
        return rand(2, 1150);
    }

    /**
     * fetch the DC roll.
     */
    protected function fetchDCRoll(): int
    {
        return rand(1, 100);
    }
}
