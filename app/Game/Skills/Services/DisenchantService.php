<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\CharacterInventory\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
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

        return $this;
    }

    public function disenchantItem(Character $character, Item $item, bool $doNotSendResponse = false): array
    {

        $inventory = Inventory::where('character_id', $character->id)->first();

        $foundItem = InventorySlot::where('equipped', false)->where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        if (is_null($foundItem)) {
            return $this->errorResult($item->affix_name . ' Cannot be disenchanted. Not found in inventory.');
        }

        if (is_null($foundItem->item->item_suffix_id) && is_null($foundItem->item->item_prefix_id)) {
            return $this->errorResult($item->affix_name . ' Cannot be disenchanted. Has no enchantments attached.');
        }

        if (! is_null($foundItem)) {
            if ($foundItem->item->type === 'quest') {
                return $this->errorResult('Quest items cannot be disenchanted.');
            }

            $this->setUp($character)->disenchantWithSkill($foundItem);

            event(new UpdateTopBarEvent($character->refresh()));
        }

        if ($doNotSendResponse) {
            return $this->successResult();
        }

        return $this->successResult([
            'message' => 'Disenchanted item ' . $item->affix_name . ' Check server message tab for Gold Dust output.',
            'inventory' => [
                'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForType('inventory'),
            ],
        ]);
    }

    /**
     * Disenchant the item.
     */
    public function disenchantWithSkill(InventorySlot $slot): void
    {
        if ($this->character->gold_dust >= MaxCurrenciesValue::MAX_GOLD_DUST) {
            $slot->delete();

            $affixData = resolve(EnchantingService::class)->fetchAffixes($this->character->refresh());

            event(new UpdateSkillEvent($this->disenchantingSkill));

            event(new UpdateCharacterEnchantingList(
                $this->character->user,
                $affixData['affixes'],
                $affixData['character_inventory'],
            ));

            return;
        }

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

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
     */
    public function disenchantItemWithSkill(): void
    {

        $characterRoll = $this->skillCheckService->characterRoll($this->disenchantingSkill);
        $dcCheck = $this->skillCheckService->getDCCheck($this->disenchantingSkill);

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
     */
    protected function updateGoldDust(Character $character, bool $failedCheck = false): int
    {

        $goldDust = ! $failedCheck ? rand(2, 1150) : 1;

        $goldDust = $goldDust + $goldDust * $this->disenchantingSkill->bonus;

        $characterTotalGoldDust = $character->gold_dust + $goldDust;
        if (! $failedCheck) {

            $dc = 500 - 500 * 0.10;
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
            'gold_dust' => $characterTotalGoldDust,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        return $goldDust;
    }

    /**
     * fetch the DC roll.
     */
    protected function fetchDCRoll(): int
    {
        return rand(1, 500);
    }
}
