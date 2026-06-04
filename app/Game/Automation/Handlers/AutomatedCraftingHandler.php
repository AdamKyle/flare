<?php

namespace App\Game\Automation\Handlers;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Automation\Contracts\AutomatedCraftingLogger;
use App\Game\Automation\Enums\AutomatedCraftingResultType;
use App\Game\Automation\Values\AutomatedCraftingAttemptTracker;
use App\Game\Automation\Values\AutomatedCraftingResult;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\CraftingService;

class AutomatedCraftingHandler
{
    private const DEFAULT_MAX_ATTEMPTS = 50;

    private const MINIMUM_TRAINING_CRAFTS = 50;

    private Character $character;

    private int $targetItemId;

    private AutomatedCraftingLogger $automatedCraftingLogger;

    private bool $craftForNpc = false;

    private bool $craftForEvent = false;

    private int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS;

    private ?Item $targetItem = null;

    private string $craftingType = '';

    private ?Skill $craftingSkill = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    /**
     * Create the automated crafting handler.
     */
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly ShopService $shopService,
        private readonly AutomatedCraftingAttemptTracker $automatedCraftingAttemptTracker,
        private readonly AutomatedCraftingResult $automatedCraftingResult,
    ) {}

    /**
     * Set up the handler.
     */
    public function setUp(
        Character $character,
        int $targetItemId,
        AutomatedCraftingLogger $automatedCraftingLogger,
    ): AutomatedCraftingHandler {
        $this->character = $character;
        $this->targetItemId = $targetItemId;
        $this->automatedCraftingLogger = $automatedCraftingLogger;
        $this->craftForNpc = false;
        $this->craftForEvent = false;
        $this->maxAttempts = self::DEFAULT_MAX_ATTEMPTS;
        $this->targetItem = null;
        $this->craftingType = '';
        $this->craftingSkill = null;
        $this->factionLoyaltyNpc = null;

        return $this;
    }

    /**
     * Set craft for NPC.
     */
    public function setCraftForNpc(): AutomatedCraftingHandler
    {
        $this->craftForNpc = true;

        return $this;
    }

    /**
     * Set craft for event.
     */
    public function setCraftForEvent(): AutomatedCraftingHandler
    {
        $this->craftForEvent = true;

        return $this;
    }

    /**
     * Set the faction loyalty NPC.
     */
    public function setFactionLoyaltyNpc(FactionLoyaltyNpc $factionLoyaltyNpc): AutomatedCraftingHandler
    {
        $this->factionLoyaltyNpc = $factionLoyaltyNpc;

        return $this;
    }

    /**
     * Set max failed roll attempts.
     */
    public function setMaxAttempts(int $maxAttempts): AutomatedCraftingHandler
    {
        if ($maxAttempts > 0) {
            $this->maxAttempts = $maxAttempts;
        }

        return $this;
    }

    /**
     * Handle automated crafting.
     */
    public function handle(): AutomatedCraftingResult
    {
        if (! $this->setTargetItem()) {
            return $this->finish(AutomatedCraftingResultType::ITEM_NOT_FOUND);
        }

        $this->craftingType = $this->getCraftingType($this->targetItem);

        if (! $this->setCraftingSkill()) {
            return $this->finish(AutomatedCraftingResultType::NO_CRAFTING_SKILL, $this->targetItem);
        }

        $this->automatedCraftingAttemptTracker->setUp($this->isBelowTargetLevel());

        if ($this->isBelowTargetLevel()) {
            return $this->attemptTrainingCrafting();
        }

        return $this->attemptTargetCrafting();
    }

    /**
     * Set the target item.
     */
    private function setTargetItem(): bool
    {
        $targetItem = Item::find($this->targetItemId);

        if (is_null($targetItem)) {
            return false;
        }

        $this->targetItem = $targetItem;

        return true;
    }

    /**
     * Set the crafting skill.
     */
    private function setCraftingSkill(): bool
    {
        $craftingSkill = $this->craftingService->getCraftingSkillForAutomation($this->character, $this->craftingType);

        if (is_null($craftingSkill)) {
            return false;
        }

        $this->craftingSkill = $craftingSkill;

        return true;
    }

    /**
     * Attempt target item crafting.
     */
    private function attemptTargetCrafting(): AutomatedCraftingResult
    {
        while ($this->shouldContinueTargetCrafting()) {
            if ($this->automatedCraftingAttemptTracker->getFailedRolls() >= $this->maxAttempts) {
                return $this->finish(
                    AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED,
                    $this->automatedCraftingAttemptTracker->getLastAttemptedItem()
                );
            }

            $this->refreshState();

            if (! $this->setCraftingSkill()) {
                return $this->finish(AutomatedCraftingResultType::NO_CRAFTING_SKILL, $this->targetItem);
            }

            if ($this->isBelowTargetLevel()) {
                return $this->attemptTrainingCrafting();
            }

            if (! $this->canAfford($this->targetItem)) {
                return $this->finish(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $this->targetItem);
            }

            $this->craftItem($this->targetItem);
        }

        return $this->finish(
            AutomatedCraftingResultType::CRAFTED_TARGET_ITEM,
            $this->targetItem,
            $this->automatedCraftingAttemptTracker->hasCraftedTargetItem()
        );
    }

    /**
     * Attempt training item crafting.
     */
    private function attemptTrainingCrafting(): AutomatedCraftingResult
    {
        while ($this->automatedCraftingAttemptTracker->getSuccessfulTrainingCrafts() < self::MINIMUM_TRAINING_CRAFTS) {
            if ($this->automatedCraftingAttemptTracker->getFailedRolls() >= $this->maxAttempts) {
                return $this->finish(
                    AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED,
                    $this->automatedCraftingAttemptTracker->getLastAttemptedItem()
                );
            }

            $this->refreshState();

            if (! $this->setCraftingSkill()) {
                return $this->finish(AutomatedCraftingResultType::NO_CRAFTING_SKILL, $this->targetItem);
            }

            $trainingItem = $this->getTrainingItem();

            if (is_null($trainingItem)) {
                return $this->finish(AutomatedCraftingResultType::NO_TRAINING_ITEM, $this->targetItem);
            }

            if (! $this->canAfford($trainingItem)) {
                return $this->finish(AutomatedCraftingResultType::NOT_ENOUGH_GOLD, $trainingItem);
            }

            $this->craftItem($trainingItem);
        }

        return $this->finish(
            AutomatedCraftingResultType::CRAFTED_TRAINING_ITEM,
            $this->automatedCraftingAttemptTracker->getLastAttemptedItem()
        );
    }

    /**
     * Refresh the character state.
     */
    private function refreshState(): void
    {
        $this->character = $this->character->refresh();
    }

    /**
     * Should target crafting continue?
     */
    private function shouldContinueTargetCrafting(): bool
    {
        if (! $this->craftForNpc) {
            return ! $this->automatedCraftingAttemptTracker->hasCraftedTargetItem();
        }

        return $this->getRemainingTargetTaskAmount() > 0;
    }

    /**
     * Get the remaining target task amount.
     */
    private function getRemainingTargetTaskAmount(): int
    {
        if (is_null($this->factionLoyaltyNpc)) {
            return 0;
        }

        $this->factionLoyaltyNpc = $this->factionLoyaltyNpc->refresh();

        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks?->fame_tasks ?? [];

        foreach ($fameTasks as $task) {
            if (isset($task['item_id']) && $task['item_id'] === $this->targetItemId) {
                return max(0, $task['required_amount'] - $task['current_amount']);
            }
        }

        return 0;
    }

    /**
     * Is the character below the target item level?
     */
    private function isBelowTargetLevel(): bool
    {
        if (is_null($this->targetItem) || is_null($this->craftingSkill)) {
            return false;
        }

        return $this->craftingSkill->level < $this->targetItem->skill_level_required;
    }

    /**
     * Can the character afford the item?
     */
    private function canAfford(Item $item): bool
    {
        return $this->craftingService->getItemCostForAutomation($this->character, $item) <= $this->character->gold;
    }

    /**
     * Craft the item.
     */
    private function craftItem(Item $item): bool
    {
        $goldBeforeCrafting = $this->character->gold;
        $isTargetItem = $this->isTargetItem($item);

        $crafted = $this->craftingService->craft($this->character, [
            'item_to_craft' => $item->id,
            'type' => $this->getCraftingType($item),
            'craft_for_npc' => $isTargetItem && $this->craftForNpc,
            'craft_for_event' => $isTargetItem && $this->craftForEvent,
            'skip_crafting_timeout' => true,
        ]);

        if ($crafted && ! $isTargetItem) {
            $this->sellTrainingItem($this->craftingService->getLastCraftedInventorySlotId());
        }

        $this->character = $this->character->refresh();

        $this->automatedCraftingAttemptTracker->trackAttempt(
            $item,
            max(0, $goldBeforeCrafting - $this->character->gold),
            $crafted,
            $isTargetItem
        );

        return $crafted;
    }

    /**
     * Sell a crafted training item.
     */
    private function sellTrainingItem(?int $inventorySlotId): void
    {
        if (is_null($inventorySlotId)) {
            return;
        }

        $character = $this->character->refresh()->load('inventory.slots.item');

        $this->shopService->sellSpecificItem($character, $inventorySlotId);
    }

    /**
     * Is the item the target item?
     */
    private function isTargetItem(Item $item): bool
    {
        if (is_null($this->targetItem)) {
            return false;
        }

        return $item->id === $this->targetItem->id;
    }

    /**
     * Get the training item.
     */
    private function getTrainingItem(): ?Item
    {
        if (is_null($this->targetItem) || is_null($this->craftingSkill)) {
            return null;
        }

        $items = Item::where('can_craft', true)
            ->where('skill_level_required', '<=', $this->craftingSkill->level)
            ->where('skill_level_trivial', '>=', $this->craftingSkill->level)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNull('specialty_type')
            ->doesntHave('appliedHolyStacks')
            ->doesntHave('sockets')
            ->orderBy('skill_level_required', 'desc');

        if ($this->isWeaponCraftingItem($this->targetItem)) {
            $items->whereIn('type', ItemType::validWeapons());
        } elseif ($this->shouldUseCraftingType($this->targetItem)) {
            $items->where('crafting_type', $this->targetItem->crafting_type);
        } else {
            $items->where('type', $this->targetItem->type);
        }

        return $items->first();
    }

    /**
     * Is the item crafted through weapon crafting?
     */
    private function isWeaponCraftingItem(Item $item): bool
    {
        return in_array($item->type, ItemType::validWeapons());
    }

    /**
     * Should use the crafting type instead of item type?
     */
    private function shouldUseCraftingType(Item $item): bool
    {
        return in_array($item->crafting_type, ['armour', 'ring', 'spell']);
    }

    /**
     * Get the crafting type.
     */
    private function getCraftingType(Item $item): string
    {
        if (in_array($item->type, [
            'body',
            'shield',
            'leggings',
            'feet',
            'sleeves',
            'helmet',
            'gloves',
        ])) {
            return 'armour';
        }

        if ($item->type === 'spell-damage' || $item->type === 'spell-healing') {
            return 'spell';
        }

        if (in_array($item->type, ItemType::validWeapons())) {
            return $item->type;
        }

        return $item->crafting_type;
    }

    /**
     * Finish automated crafting.
     */
    private function finish(
        AutomatedCraftingResultType $automatedCraftingResultType,
        ?Item $craftedItem = null,
        bool $craftedTargetItem = false,
    ): AutomatedCraftingResult {
        $automatedCraftingResult = $this->automatedCraftingResult
            ->setUp($automatedCraftingResultType, $this->targetItemId)
            ->setCraftedItemId($craftedItem?->id)
            ->setCraftedItemName($craftedItem?->affix_name)
            ->setCraftingType($this->craftingType)
            ->setTargetItemLevel($this->targetItem?->skill_level_required ?? 0)
            ->setCurrentSkillLevel($this->craftingSkill?->level ?? 0)
            ->setStartedBelowTargetLevel($this->automatedCraftingAttemptTracker->hasStartedBelowTargetLevel())
            ->setCraftedTargetItem($craftedTargetItem || $this->automatedCraftingAttemptTracker->hasCraftedTargetItem())
            ->setAttempts($this->automatedCraftingAttemptTracker->getAttempts())
            ->setFailedRolls($this->automatedCraftingAttemptTracker->getFailedRolls())
            ->setGoldSpent($this->automatedCraftingAttemptTracker->getGoldSpent())
            ->setSuccessfulTargetCrafts($this->automatedCraftingAttemptTracker->getSuccessfulTargetCrafts())
            ->setSuccessfulTrainingCrafts($this->automatedCraftingAttemptTracker->getSuccessfulTrainingCrafts());

        $this->automatedCraftingLogger->log($automatedCraftingResult);

        return $automatedCraftingResult;
    }
}
