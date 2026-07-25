<?php

namespace App\Game\BatchCrafting\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GemBagSlot;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\SetSlot;
use App\Flare\Models\Skill;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Values\ItemHolyValue;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Facades\App\Flare\Calculators\SkillXPCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BatchCraftingProcessor
{
    private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService;

    private readonly EventBatchEnchantingAffixSelector $eventBatchEnchantingAffixSelector;

    private readonly SetHandsValidation $setHandsValidation;

    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly AlchemyService $alchemyService,
        private readonly TrinketCraftingService $trinketCraftingService,
        private readonly EnchantingService $enchantingService,
        private readonly HolyItemService $holyItemService,
        private readonly MultiInventoryActionService $multiInventoryActionService,
        private readonly UseItemService $useItemService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly InventorySetService $inventorySetService,
        private readonly HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal,
        private readonly HandleUpdatingEnchantingGlobalEventGoal $handleUpdatingEnchantingGlobalEventGoal,
        private readonly ServerMessageHandler $serverMessageHandler,
        ?GlobalEventGoalEligibilityService $globalEventGoalEligibilityService = null,
        ?EventBatchEnchantingAffixSelector $eventBatchEnchantingAffixSelector = null,
        ?SetHandsValidation $setHandsValidation = null,
    ) {
        $this->globalEventGoalEligibilityService = $globalEventGoalEligibilityService ?? new GlobalEventGoalEligibilityService();
        $this->eventBatchEnchantingAffixSelector = $eventBatchEnchantingAffixSelector ?? new EventBatchEnchantingAffixSelector();
        $this->setHandsValidation = $setHandsValidation ?? new SetHandsValidation();
    }

    public function craftSetQueue(): array
    {
        return $this->craftExperienceTargets(null);
    }

    public function craftSetPlannerTargets(): array
    {
        return [
            ['key' => 'left_hand', 'label' => 'Left Hand', 'category' => 'hand', 'optional' => true, 'candidate_scope' => 'hand'],
            ['key' => 'right_hand', 'label' => 'Right Hand', 'category' => 'hand', 'optional' => true, 'candidate_scope' => 'hand'],
            ['key' => 'body', 'label' => 'Body', 'category' => 'armour', 'optional' => false, 'type' => 'body', 'crafting_type' => 'armour'],
            ['key' => 'leggings', 'label' => 'Leggings', 'category' => 'armour', 'optional' => false, 'type' => 'leggings', 'crafting_type' => 'armour'],
            ['key' => 'sleeves', 'label' => 'Sleeves', 'category' => 'armour', 'optional' => false, 'type' => 'sleeves', 'crafting_type' => 'armour'],
            ['key' => 'gloves', 'label' => 'Gloves', 'category' => 'armour', 'optional' => false, 'type' => 'gloves', 'crafting_type' => 'armour'],
            ['key' => 'feet', 'label' => 'Feet', 'category' => 'armour', 'optional' => false, 'type' => 'feet', 'crafting_type' => 'armour'],
            ['key' => 'helmet', 'label' => 'Helmet', 'category' => 'armour', 'optional' => false, 'type' => 'helmet', 'crafting_type' => 'armour'],
            ['key' => 'ring_0', 'label' => 'Ring 1', 'category' => 'ring', 'optional' => false, 'type' => 'ring', 'crafting_type' => 'ring'],
            ['key' => 'ring_1', 'label' => 'Ring 2', 'category' => 'ring', 'optional' => false, 'type' => 'ring', 'crafting_type' => 'ring'],
            ['key' => 'spell-damage', 'label' => 'Damage Spell', 'category' => 'spell', 'optional' => false, 'type' => 'spell-damage', 'crafting_type' => 'spell'],
            ['key' => 'spell-healing', 'label' => 'Healing Spell', 'category' => 'spell', 'optional' => false, 'type' => 'spell-healing', 'crafting_type' => 'spell'],
        ];
    }

    public function craftEnchantSetPlanKeys(array $queue): array
    {
        $keys = [];
        $ringIndex = 0;

        foreach ($queue as $target) {
            if (isset($target['key'])) {
                $keys[] = $target['key'];

                continue;
            }

            if ($target['type'] === ItemType::RING->value) {
                $keys[] = 'ring_' . $ringIndex;
                $ringIndex++;

                continue;
            }

            $keys[] = $target['type'];
        }

        return $keys;
    }

    public function craftSetPreviewItems(Character $character, array $selectedItemIds = []): array
    {
        $queue = $this->craftSetPlannerTargets();
        $keys = $this->craftEnchantSetPlanKeys($queue);

        return collect($queue)
            ->values()
            ->map(function (array $target, int $index) use ($character, $keys, $selectedItemIds): array {
                $key = $keys[$index] ?? (string) $index;
                $isHand = ($target['category'] ?? null) === 'hand';
                $candidates = $isHand
                    ? $this->craftableHandCandidates($character)
                    : $this->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type']);
                $selectedItemId = isset($selectedItemIds[$key]) ? (int) $selectedItemIds[$key] : null;
                $selectedItem = $isHand && is_null($selectedItemId)
                    ? null
                    : $this->resolveSelectedOrHighestCraftableItem($candidates, $selectedItemId);

                return [
                    'key' => $key,
                    'target' => $target,
                    'requested_selected_item_id' => $selectedItemId,
                    'selected_item_available' => is_null($selectedItemId) || ! is_null($selectedItem),
                    'included' => ! ($target['optional'] ?? false) || ! is_null($selectedItem),
                    'item' => $selectedItem,
                    'available_items' => $candidates->map(fn ($candidate) => [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'cost' => (int) ($candidate->cost ?? 0),
                        'skill_level_required' => (int) $candidate->skill_level_required,
                        'type' => $candidate->type,
                        'handedness' => $isHand ? $this->setHandsValidation->handedness($candidate) : null,
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    public function craftableHandCandidates(Character $character): Collection
    {
        $candidateIds = collect();

        foreach (ItemType::validWeapons() as $weaponType) {
            try {
                $candidateIds = $candidateIds->merge(
                    $this->specificCraftableItems($character, $weaponType)
                        ->filter(fn ($item) => $item->type === $weaponType)
                        ->pluck('id')
                );
            } catch (\Throwable) {
            }
        }

        try {
            $candidateIds = $candidateIds->merge(
                $this->specificCraftableItems($character, 'armour')
                    ->filter(fn ($item) => $item->type === 'shield')
                    ->pluck('id')
            );
        } catch (\Throwable) {
        }

        return Item::whereIn('id', $candidateIds->unique()->values())
            ->orderByDesc('skill_level_required')
            ->orderByDesc('cost')
            ->orderBy('id')
            ->get();
    }

    public function craftSetExecutionQueue(Character $character, array $plan): array
    {
        $selectedItemIds = collect($plan)->map(fn ($entry) => is_array($entry) ? ($entry['selected_item_id'] ?? null) : null)->all();

        return collect($this->craftSetPreviewItems($character, $selectedItemIds))
            ->filter(fn (array $entry) => $entry['included'] && ! is_null($entry['item']))
            ->map(function (array $entry): array {
                $item = $entry['item'];

                if (in_array($item->type, ItemType::validWeapons(), true)) {
                    $craftingType = 'weapon';
                } elseif ($item->type === ArmourType::SHIELD->value) {
                    $craftingType = 'armour';
                } else {
                    $craftingType = $entry['target']['crafting_type'];
                }

                return [
                    'key' => $entry['key'],
                    'type' => $item->type,
                    'crafting_type' => $craftingType,
                ];
            })
            ->values()
            ->all();
    }

    public function craftableItemCandidatesForTarget(Character $character, string $type, string $craftingType): Collection
    {
        $candidateCraftingType = in_array($type, ItemType::validWeapons(), true)
            ? $type
            : ($type === ArmourType::SHIELD->value ? 'armour' : $craftingType);

        try {
            $candidateIds = $this->specificCraftableItems($character, $candidateCraftingType)
                ->filter(fn ($item) => $item->type === $type)
                ->pluck('id');
        } catch (\Throwable) {
            return collect();
        }

        if ($candidateIds->isEmpty()) {
            return collect();
        }

        return Item::whereIn('id', $candidateIds)
            ->orderByDesc('skill_level_required')
            ->orderByDesc('cost')
            ->orderBy('id')
            ->get();
    }

    private function resolveSelectedOrHighestCraftableItem(Collection $candidates, ?int $selectedItemId): ?Item
    {
        if (! is_null($selectedItemId)) {
            return $candidates->first(fn ($candidate) => (int) $candidate->id === $selectedItemId);
        }

        return $candidates->first();
    }

    public function isEnchantableSetItem(?Item $item): bool
    {
        return ! is_null($item)
            && ! in_array($item->type, ['trinket', 'artifact', 'alchemy', 'gem', 'quest'], true)
            && is_null($item->item_prefix_id)
            && is_null($item->item_suffix_id);
    }

    public function processOneTick(BatchCrafting $batchCrafting, Character $character): array
    {
        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);

        return match ($type) {
            BatchCraftingType::CRAFT => $this->processCraft($batchCrafting, $character, $disposition),
            BatchCraftingType::CRAFT_AND_ENCHANT => $this->processCraftAndEnchant($batchCrafting, $character, $disposition),
            BatchCraftingType::ENCHANT => $this->processEnchant($batchCrafting, $character, $disposition),
            BatchCraftingType::ALCHEMY => $this->processAlchemy($batchCrafting, $character, $disposition),
            BatchCraftingType::HOLY_OILS => $this->processHolyOils($batchCrafting, $character, $disposition),
            BatchCraftingType::TRINKETRY => $this->processTrinketry($batchCrafting, $character, $disposition),
        };
    }

    private function processCraft(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'experience';

        if ($craftMode === 'event') {
            return $this->processEventCraft($batchCrafting, $character);
        }

        if ($craftMode === 'experience') {
            $requiredCapacity = $this->retainedCraftedItemsSetSlotsForExperience($disposition, $progress);

            if ($this->shouldCommitKeptOutput($disposition)) {
                $capacityEndReason = $this->retainedOutputCapacityEndReason($batchCrafting, $character, $requiredCapacity);

                if (! is_null($capacityEndReason)) {
                    return ['end_reason' => $capacityEndReason];
                }
            }

            $chunkSize = $this->experienceCycleChunkSize($progress);

            $result = $this->processRepeatedActions($chunkSize, function () use ($batchCrafting, $character, $disposition, $progress) {
                return $this->processCraftExperience($batchCrafting->refresh(), $character->refresh(), $disposition, $batchCrafting->refresh()->progress ?? $progress, true);
            });

            $this->recordExperienceCycleProgress($batchCrafting, $progress, $result);

            return $result;
        }

        if ($craftMode === 'specific_item') {
            return $this->processCraftSpecificItem($batchCrafting, $character, $disposition, $progress);
        }

        if ($craftMode === 'craft_set') {
            return $this->processCraftSetSingle($batchCrafting, $character, $disposition);
        }

        return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
    }

    private function processCraftSetSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_set_queue'] ?? [];
        $index = (int) ($progress['craft_set_index'] ?? 0);

        if (empty($queue) || $index >= count($queue)) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_SET_COMPLETE];
        }

        $capacityEndReason = $this->retainedOutputCapacityEndReason($batchCrafting, $character, 1);

        if (! is_null($capacityEndReason)) {
            return ['end_reason' => $capacityEndReason];
        }

        $target = $queue[$index];
        $keys = $progress['craft_set_keys'] ?? $this->craftEnchantSetPlanKeys($queue);
        $key = $keys[$index] ?? (string) $index;
        $selectedItemId = $progress['craft_set_selected_item_ids'][$key] ?? null;
        $item = is_null($selectedItemId)
            ? $this->highestCraftableItemForTarget($character, $target['type'], $target['crafting_type'])
            : $this->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type'])
                ->first(fn ($candidate) => (int) $candidate->id === (int) $selectedItemId);

        if (is_null($item)) {
            $targetLabel = $this->finiteSetTargetLabel($key);
            $progress['invalid_plan_message'] = 'Batch Crafting stopped because the selected item for ' . $targetLabel . ' is no longer available or craftable.';
            $batchCrafting->update(['progress' => $progress]);

            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, 'actions' => [[
                'action' => 'craft_set',
                'status' => 'stopped',
                'plan_key' => $key,
                'target_label' => $targetLabel,
                'failure' => $progress['invalid_plan_message'],
            ]]];
        }

        $craftResult = $this->craftForResolvedDestination(
            $batchCrafting,
            $character->refresh(),
            $item,
            $target['crafting_type'],
            $disposition,
        );

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            $result = ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_set',
                'status' => 'failed',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];

            if (($craftResult['reason'] ?? null) === 'destination_failed') {
                $result['end_reason'] = BatchCraftingEndReason::FAILED;
            }

            return $result;
        }

        $craftedItem = $craftResult['item'];
        $craftedItemSnapshot = $this->itemDetails($craftedItem, null, false);
        $dispositionResult = $this->dispositionForCraftResult(
            $batchCrafting,
            $character->refresh(),
            $craftedItem,
            $disposition,
            $craftResult['destination'] ?? null,
        );

        $result = [
            'counts' => $this->mergeCounts(['crafted_count' => 1], $dispositionResult['counts']),
            'actions' => [array_merge(['action' => 'craft_set', 'crafted_item' => $craftedItemSnapshot], $dispositionResult['details'])],
            'kept_output_committed' => $this->shouldCommitKeptOutput($disposition),
        ];

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_set_index'] = $index + 1;
        $progress['craft_set_completed'] = ((int) ($progress['craft_set_completed'] ?? 0)) + 1;
        $progress['craft_set_current_item'] = $craftedItemSnapshot;
        $batchCrafting->update(['progress' => $progress]);

        return $result;
    }

    private function processCraftAndEnchant(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'experience';

        if ($craftMode === 'experience') {
            if ($this->isEnchantingSkillMaxed($character) && $this->allCraftingSkillsMaxed($character)) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }

            $requiredCapacity = $this->retainedCraftedItemsSetSlotsForExperience($disposition, $progress);

            if ($this->shouldCommitKeptOutput($disposition)) {
                $capacityEndReason = $this->retainedOutputCapacityEndReason($batchCrafting, $character, $requiredCapacity);

                if (! is_null($capacityEndReason)) {
                    return ['end_reason' => $capacityEndReason];
                }
            }

            $chunkSize = $this->experienceCycleChunkSize($progress);

            $result = $this->processRepeatedActions($chunkSize, function () use ($batchCrafting, $character, $disposition, $progress) {
                return $this->processCraftAndEnchantExperience($batchCrafting->refresh(), $character->refresh(), $disposition, $batchCrafting->refresh()->progress ?? $progress);
            });

            $this->recordExperienceCycleProgress($batchCrafting, $progress, $result);

            return $result;
        }

        if ($craftMode === 'specific_item') {
            return $this->processCraftAndEnchantSpecificItem($batchCrafting, $character, $disposition, $progress);
        }

        if ($craftMode === 'craft_enchant_set') {
            return $this->processCraftEnchantSetSingle($batchCrafting, $character, $disposition);
        }

        return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
    }

    private function processCraftAndEnchantSpecificItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $completedSoFar = (int) ($progress['craft_enchant_specific_count'] ?? 0);

        if ($craftAmount > 0 && $completedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        return $this->processCraftAndEnchantSpecificItemSingle($batchCrafting, $character, $disposition, $progress);
    }

    private function processCraftAndEnchantSpecificItemSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $completedSoFar = (int) ($progress['craft_enchant_specific_count'] ?? 0);

        if ($craftAmount > 0 && $completedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        if (($progress['craft_enchant_phase'] ?? 'craft') === 'enchant') {
            $result = $this->processCraftAndEnchantEnchantPhase($batchCrafting, $character, $disposition, $progress);

            $enchantedCount = $result['counts']['enchanted_count'] ?? 0;
            $noEndReason = ! isset($result['end_reason']);
            $retainedOutputOk = ! $this->shouldCommitKeptOutput($disposition) || ($result['kept_output_committed'] ?? false) === true;

            if ($noEndReason && $enchantedCount > 0 && $retainedOutputOk) {
                $updatedProgress = $batchCrafting->fresh()->progress ?? [];
                $updatedProgress['craft_enchant_specific_count'] = $completedSoFar + 1;
                $batchCrafting->update(['progress' => $updatedProgress]);
            }

            return $result;
        }

        $capacityEndReason = $this->retainedOutputCapacityEndReason($batchCrafting, $character, 1);

        if (! is_null($capacityEndReason)) {
            return ['end_reason' => $capacityEndReason];
        }

        $craftingType = $progress['specific_crafting_type'] ?? null;
        $itemId = (int) ($progress['specific_item_id'] ?? 0);

        if (is_null($craftingType) || $itemId <= 0) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $craftableItems = $this->specificCraftableItems($character, $craftingType);

        $craftableItem = $craftableItems->first(fn ($item) => (int) $item->id === $itemId);
        $item = is_null($craftableItem) ? null : Item::find($craftableItem->id);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $craftResult = $this->craftForResolvedDestination(
            $batchCrafting,
            $character->refresh(),
            $item,
            $craftingType,
            $disposition,
        );

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];
        }

        $progress['craft_enchant_phase'] = 'enchant';
        $progress['pending_enchant_item_id'] = $craftResult['item']->id;
        $progress['pending_enchant_destination'] = $craftResult['destination'] ?? null;
        $progress['craft_enchant_specific_surviving_crafted_count'] = 1;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'craft_and_enchant',
            'status' => 'crafted',
            'crafted_item' => $this->itemDetails($craftResult['item']),
        ]]];
    }

    private function processCraftAndEnchantEnchantPhase(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $pendingItemId = $progress['pending_enchant_item_id'] ?? null;
        $pendingDestination = is_array($progress['pending_enchant_destination'] ?? null)
            ? $progress['pending_enchant_destination']
            : null;
        $index = (int) ($progress['craft_enchant_index'] ?? 0);

        if (is_null($pendingItemId)) {
            $progress['craft_enchant_phase'] = 'craft';
            $progress['craft_enchant_specific_surviving_crafted_count'] = 0;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'No pending item to enchant.',
            ]]];
        }

        $item = Item::find($pendingItemId);

        if (is_null($item)) {
            $this->removeDestinationItem($pendingDestination);
            $progress['craft_enchant_phase'] = 'craft';
            $progress['pending_enchant_item_id'] = null;
            $progress['pending_enchant_destination'] = null;
            $progress['craft_enchant_specific_surviving_crafted_count'] = 0;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'Crafted item was removed before enchanting. A replacement will be crafted.',
            ]]];
        }

        if ($this->shouldCommitKeptOutput($disposition) && is_null($pendingDestination)) {
            $this->discardPartialBatchItem($item);
            $progress['craft_enchant_phase'] = 'craft';
            $progress['pending_enchant_item_id'] = null;
            $progress['pending_enchant_destination'] = null;
            $progress['craft_enchant_specific_surviving_crafted_count'] = 0;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'The crafted item had no retained destination and was discarded. A replacement will be crafted.',
            ]]];
        }

        $craftedSnapshot = $this->itemDetails($item);
        $requestedAffixIds = $this->resolveIntendedAffixIdsForEnchant($character, $progress['enchant_affix_ids'] ?? null);

        if ($this->intBlockedForEnchant($character, $requestedAffixIds)) {
            $this->removeDestinationItem($pendingDestination);

            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'int_stop_affix_ids' => $requestedAffixIds, 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'stopped',
                'crafted_item' => $craftedSnapshot,
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $suppressEnchantSuccessMessage = $this->shouldCommitKeptOutput($disposition);
        $enchantAttempt = $this->tryEnchantItem($character, $item, $requestedAffixIds, $suppressEnchantSuccessMessage);

        if (! $enchantAttempt['attempted']) {
            $this->removeDestinationItem($pendingDestination);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'crafted_item' => $craftedSnapshot,
                'failure' => 'Enchanting service did not apply an enchantment.',
            ]]];
        }

        if (is_null($enchantAttempt['item'])) {
            $this->removeDestinationItem($pendingDestination);
            $progress['craft_enchant_phase'] = 'craft';
            $progress['pending_enchant_item_id'] = null;
            $progress['pending_enchant_destination'] = null;
            $progress['craft_enchant_specific_surviving_crafted_count'] = 0;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['destroyed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'destroyed',
                'destroyed_item' => $this->removedItemDetails($craftedSnapshot, 'destroyed'),
                'failure' => 'The item shattered while enchanting and was destroyed. A replacement will be crafted.',
            ]]];
        }

        $enchantedItem = $enchantAttempt['item'];

        if (! $this->requestedAffixesSatisfied($enchantedItem, $enchantAttempt['affix_ids'])) {
            $this->removeDestinationItem($pendingDestination);
            $this->discardPartialBatchItem($enchantedItem);

            $progress['craft_enchant_phase'] = 'craft';
            $progress['pending_enchant_item_id'] = null;
            $progress['pending_enchant_destination'] = null;
            $progress['craft_enchant_specific_surviving_crafted_count'] = 0;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'partial_enchant_discarded',
                'crafted_item' => $craftedSnapshot,
                'failure' => 'The item did not receive every requested enchantment and was discarded. A replacement will be crafted.',
            ]]];
        }

        $action = [
            'action' => 'craft_and_enchant',
            'crafted_item' => $craftedSnapshot,
            'enchanted_item' => $this->itemDetails($enchantedItem),
            'enchant_affix_name' => implode(', ', $enchantAttempt['affix_names']),
        ];
        $counts = ['enchanted_count' => 1];

        $this->replaceDestinationItem($pendingDestination, $enchantedItem);
        $dispositionResult = $this->dispositionForCraftResult(
            $batchCrafting,
            $character->refresh(),
            $enchantedItem,
            $disposition,
            $pendingDestination,
        );
        $counts = $this->mergeCounts($counts, $dispositionResult['counts']);
        $action = array_merge($action, $dispositionResult['details']);

        $result = [
            'counts' => $counts,
            'actions' => [$action],
            'kept_output_committed' => $this->shouldCommitKeptOutput($disposition),
        ];

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_enchant_phase'] = 'craft';
        $progress['pending_enchant_item_id'] = null;
        $progress['pending_enchant_destination'] = null;
        $progress['craft_enchant_index'] = $index + 1;
        $progress['craft_enchant_specific_surviving_crafted_count'] = 0;
        $batchCrafting->update(['progress' => $progress]);

        return $result;
    }

    private function processEnchant(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['event_mode'] ?? false) === true && ($progress['event_action'] ?? null) === 'enchant') {
            return $this->processEventEnchant($batchCrafting, $character);
        }

        if (($progress['enchant_mode'] ?? 'event') === 'set') {
            return $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character) {
                return $this->processEnchantSetSingle($batchCrafting->refresh(), $character->refresh());
            });
        }

        return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
    }

    private function processEnchantSetSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $set = InventorySet::where('id', $progress['selected_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set) || $set->isBatchCraftingSet()) {
            return ['end_reason' => BatchCraftingEndReason::ENCHANT_SET_COMPLETE];
        }

        $eligibleSlot = $this->pickEnchantableSetSlot($set);

        if (is_null($eligibleSlot)) {
            return ['end_reason' => BatchCraftingEndReason::ENCHANT_SET_COMPLETE];
        }

        if ($character->isInventoryFull()) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE];
        }

        $itemSnapshotBeforeMove = $this->itemDetails($eligibleSlot->item, null, false);
        $progress['enchant_set_current_item'] = $itemSnapshotBeforeMove;
        $batchCrafting->update(['progress' => $progress]);

        if (! $this->inventorySetService->putItemFromInventorySetBackIntoCharacterInventory($character, $set, $eligibleSlot->item)) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE];
        }

        $character = $character->refresh();
        $movedSlot = $character->inventory->slots()->where('item_id', $eligibleSlot->item_id)->orderByDesc('id')->first();

        if (is_null($movedSlot)) {
            $progress['enchant_set_skipped'] = ((int) ($progress['enchant_set_skipped'] ?? 0)) + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'enchant_set',
                'status' => 'skipped',
                'failure' => 'Item could not be moved out of the set for enchanting.',
            ]]];
        }

        $affixIds = $this->resolveIntendedAffixIdsForEnchant($character, $progress['enchant_affix_ids'] ?? null);

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            $this->inventorySetService->assignItemToSet($set, $movedSlot->refresh());

            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'int_stop_affix_ids' => $affixIds, 'actions' => [[
                'action' => 'enchant_set',
                'status' => 'stopped',
                'crafted_item' => $itemSnapshotBeforeMove,
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $enchanted = $this->tryEnchantSlot($character, $movedSlot, $affixIds);
        $enchantedSnapshot = $enchanted ? $this->itemDetails($movedSlot->refresh()->item, $movedSlot->id, false) : null;

        $character = $character->refresh();
        $this->inventorySetService->assignItemToSet($set, $movedSlot->refresh());

        $progress = $batchCrafting->fresh()->progress ?? [];

        if ($enchanted) {
            $progress['enchant_set_completed'] = ((int) ($progress['enchant_set_completed'] ?? 0)) + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['enchanted_count' => 1], 'actions' => [[
                'action' => 'enchant_set',
                'status' => 'enchanted',
                'crafted_item' => $itemSnapshotBeforeMove,
                'enchanted_item' => $enchantedSnapshot,
            ]]];
        }

        $progress['enchant_set_skipped'] = ((int) ($progress['enchant_set_skipped'] ?? 0)) + 1;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['skipped_count' => 1], 'actions' => [[
            'action' => 'enchant_set',
            'status' => 'skipped',
            'crafted_item' => $itemSnapshotBeforeMove,
            'failure' => 'Enchanting service did not apply an enchantment.',
        ]]];
    }

    private function pickEnchantableSetSlot(InventorySet $set): ?SetSlot
    {
        return $set->slots()->with('item')->get()->first(function (SetSlot $slot) {
            return $this->isEnchantableSetItem($slot->item);
        });
    }

    private function processAlchemy(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $alchemyMode = $progress['alchemy_mode'] ?? 'experience';

        if ($alchemyMode === 'experience') {
            $alchemySkill = $character->skills()
                ->whereHas('baseSkill', fn ($q) => $q->where('type', \App\Game\Skills\Values\SkillTypeValue::ALCHEMY->value))
                ->with('baseSkill')
                ->first();

            if (! is_null($alchemySkill) && $alchemySkill->level >= $alchemySkill->max_level) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }
        }

        if ($alchemyMode === 'amount') {
            $craftAmount = (int) ($progress['alchemy_amount'] ?? $progress['craft_amount'] ?? 0);
            $craftedSoFar = (int) ($progress['alchemy_amount_count'] ?? 0);

            if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
                return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
            }

        } else {
            $result = $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character, $disposition) {
                return $this->processAlchemySingle($batchCrafting->refresh(), $character->refresh(), $disposition);
            });

            $this->sendAlchemyUseNowAggregateMessages($character, $disposition, $result['actions'] ?? []);

            return $result;
        }

        $result = $this->processAlchemySingle($batchCrafting, $character, $disposition);

        $this->sendAlchemyUseNowAggregateMessages($character, $disposition, $result['actions'] ?? []);

        return $result;
    }

    private function processAlchemySingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $alchemyMode = $progress['alchemy_mode'] ?? 'experience';

        if ($alchemyMode === 'experience') {
            $alchemySkill = $character->skills()
                ->whereHas('baseSkill', fn ($q) => $q->where('type', SkillTypeValue::ALCHEMY->value))
                ->with('baseSkill')
                ->first();

            if (! is_null($alchemySkill) && $alchemySkill->level >= $alchemySkill->max_level) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }
        }

        if ($alchemyMode === 'amount') {
            $craftAmount = (int) ($progress['alchemy_amount'] ?? $progress['craft_amount'] ?? 0);
            $craftedSoFar = (int) ($progress['alchemy_amount_count'] ?? 0);

            if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
                return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
            }
        }

        $items = $this->alchemyService->fetchAlchemistItems($character, false);

        if ($items->isEmpty()) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if ($alchemyMode === 'amount') {
            $alchemyItemId = (int) ($progress['alchemy_item_id'] ?? 0);
            $item = $items->first(fn ($alchemyItem) => (int) $alchemyItem->id === $alchemyItemId);

            if (is_null($item)) {
                return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
            }
        } else {
            $item = $this->xpEligibleItemFromCandidates($items, $alchemySkill?->level);

            if (is_null($item)) {
                return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
            }
        }

        $sourceItem = Item::find($item->id);
        $requiresBagCapacity = $this->alchemyDispositionRequiresBagCapacity($batchCrafting, $character, $disposition, $sourceItem);

        if ($requiresBagCapacity && ! $character->canAddToAlchemyBag(1)) {
            return ['end_reason' => BatchCraftingEndReason::ALCHEMY_BAG_FULL, 'actions' => [[
                'action' => 'alchemy',
                'status' => 'failed',
                'failure' => 'Your Alchemy Bag is full. Empty space before starting this batch.',
            ]]];
        }

        $slotAmountsBefore = AlchemyBagSlot::where('character_id', $character->id)
            ->pluck('amount', 'item_id')
            ->all();
        $currencyBefore = [
            'gold' => $character->gold,
            'gold_dust' => $character->gold_dust,
            'shards' => $character->shards,
        ];

        $this->alchemyService->transmute($character, $item->id, true, ! $requiresBagCapacity);
        $character = $character->refresh();
        $producedSlot = $this->findProducedAlchemySlot($character, $slotAmountsBefore);

        if (is_null($producedSlot)) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'alchemy',
                'alchemy_item' => $this->itemDetails(Item::find($item->id)),
                'failure' => 'Alchemy service did not produce an alchemy bag item.',
                'currency' => $this->currencyDetails($currencyBefore, $character),
            ]]];
        }

        $dispositionResult = $this->applyAlchemyDisposition($batchCrafting, $character, $producedSlot, $disposition);

        $completedRequestedAmount = false;

        if ($alchemyMode === 'amount') {
            $progress = $batchCrafting->fresh()->progress ?? [];
            $craftAmount = (int) ($progress['alchemy_amount'] ?? $progress['craft_amount'] ?? 0);
            $completedCount = ((int) ($progress['alchemy_amount_count'] ?? 0)) + 1;
            $progress['alchemy_amount_count'] = $completedCount;
            $batchCrafting->update(['progress' => $progress]);
            $completedRequestedAmount = $craftAmount > 0 && $completedCount >= $craftAmount;
        }

        $result = ['counts' => array_merge(['crafted_count' => 1], $dispositionResult['counts']), 'actions' => [[
            'action' => 'alchemy',
            'alchemy_item' => $this->itemDetails($producedSlot->item, $producedSlot->id, false),
            'currency' => $this->currencyDetails($currencyBefore, $character->refresh()),
        ] + $dispositionResult['details']]];

        if ($completedRequestedAmount) {
            $result['end_reason'] = BatchCraftingEndReason::AMOUNT_REACHED;
        }

        return $result;
    }

    private function applyAlchemyDisposition(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot, BatchCraftingDisposition $disposition): array
    {
        return match ($disposition) {
            BatchCraftingDisposition::KEEP => $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)],
            ]),
            BatchCraftingDisposition::KEEP_HIGHEST => $this->applyAlchemyKeepHighest($batchCrafting, $character, $slot),
            BatchCraftingDisposition::SELL => $this->sellAlchemySlot($character, $slot),
            BatchCraftingDisposition::DESTROY => $this->destroyAlchemySlot($slot),
            BatchCraftingDisposition::LIST => $this->listAlchemySlot($batchCrafting, $character, $slot),
            BatchCraftingDisposition::DISENCHANT => $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false, $slot->id)],
            ]),
            BatchCraftingDisposition::KEEP_BEST_SELL_REST => $this->applyAlchemyKeepBestAndRestSlot($batchCrafting, $character, $slot, 'sell'),
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST => $this->applyAlchemyKeepBestAndRestSlot($batchCrafting, $character, $slot, 'destroy'),
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST => $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false, $slot->id)],
            ]),
            BatchCraftingDisposition::USE_NOW => $this->useAlchemySlotNow($character, $slot),
        };
    }

    /**
     * Emits the linked "Kept: X in your Alchemy Bag." Server Message for a
     * disposition result that retains the produced item in the given slot,
     * then returns that same result unchanged.
     */
    private function sendAlchemyKeptInBagMessage(Character $character, AlchemyBagSlot $slot, array $result): array
    {
        $itemName = $slot->item->affix_name ?? $slot->item->name;

        $this->serverMessageHandler->sendBasicMessageWithLink(
            $character->user,
            'Kept: ' . $itemName . ' in your Alchemy Bag.',
            $slot->id,
            'alchemy_bag',
            $itemName,
        );

        return $result;
    }

    /**
     * Alchemy Bag capacity is only a real blocker for dispositions that retain
     * the produced item in the bag. Destroy/List consume the output immediately,
     * so they never need bag space. Keep Best Destroy Rest only needs space for
     * its first (retained) item; once a winner is already tracked, each new
     * candidate is either swapped in (freeing the old slot) or destroyed
     * immediately, so no extra capacity is required. Use Now only needs bag
     * space for outputs it cannot use on the character (kingdom bombs,
     * item-targeted alchemy items); usable boon items are used immediately.
     */
    private function alchemyDispositionRequiresBagCapacity(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, ?Item $item): bool
    {
        return match ($disposition) {
            BatchCraftingDisposition::DESTROY, BatchCraftingDisposition::LIST => false,
            BatchCraftingDisposition::KEEP_BEST_SELL_REST, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST => $this->alchemyKeepBestHasNoTrackedSlotYet($batchCrafting, $character),
            BatchCraftingDisposition::USE_NOW => is_null($item) || ! $this->useItemService->isAlchemyBoonItem($item),
            default => true,
        };
    }

    private function alchemyKeepBestHasNoTrackedSlotYet(BatchCrafting $batchCrafting, Character $character): bool
    {
        $progress = $batchCrafting->progress ?? [];
        $trackedSlotId = $progress['alchemy_keep_best_slot'] ?? null;

        if (is_null($trackedSlotId)) {
            return true;
        }

        $trackedSlot = AlchemyBagSlot::where('character_id', $character->id)->find($trackedSlotId);

        return is_null($trackedSlot) || is_null($trackedSlot->item);
    }

    /**
     * Alchemy equivalent of applyKeepBestAndRestItem, operating on AlchemyBagSlot
     * stacks instead of freshly crafted Items. Disenchant is not a valid Alchemy
     * loser action (alchemy items are never enchanted), so only sell/destroy apply.
     */
    private function applyAlchemyKeepBestAndRestSlot(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot, string $loserAction): array
    {
        $progress = $batchCrafting->progress ?? [];
        $trackedSlotId = $progress['alchemy_keep_best_slot'] ?? null;
        $trackedSlot = is_null($trackedSlotId) ? null : AlchemyBagSlot::where('character_id', $character->id)->find($trackedSlotId);

        if (is_null($trackedSlot) || is_null($trackedSlot->item)) {
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_best_slot' => $slot->id])]);

            return $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_best_' . $loserAction . '_rest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)],
            ]);
        }

        $newLevel = (int) ($slot->item->skill_level_required ?? 0);
        $trackedLevel = (int) ($trackedSlot->item->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $loserResult = $loserAction === 'destroy'
                ? $this->destroyAlchemySlot($trackedSlot)
                : $this->sellAlchemySlot($character, $trackedSlot);
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_best_slot' => $slot->id])]);

            return $this->sendAlchemyKeptInBagMessage($character, $slot, [
                'counts' => $this->mergeCounts(['kept_count' => 1], $loserResult['counts']),
                'details' => array_merge(['disposition' => 'keep_best_' . $loserAction . '_rest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)], $loserResult['details']),
            ]);
        }

        $loserResult = $loserAction === 'destroy'
            ? $this->destroyAlchemySlot($slot)
            : $this->sellAlchemySlot($character, $slot);

        return [
            'counts' => $loserResult['counts'],
            'details' => array_merge(['disposition' => 'keep_best_' . $loserAction . '_rest'], $loserResult['details']),
        ];
    }

    /**
     * Use Now only uses alchemy items that are valid character boons (not kingdom
     * bombs, not kingdom/item-targeted alchemy items). Anything else, and anything
     * blocked by the existing 10-boon/max-duration limit, stays in the Alchemy Bag.
     */
    private function useAlchemySlotNow(Character $character, AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id, false);

        if (! $this->useItemService->isAlchemyBoonItem($slot->item)) {
            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $itemDetails],
            ];
        }

        $result = $this->useItemService->useSingleAlchemyItem($character, $slot);

        if (($result['status'] ?? 0) !== 200) {
            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $itemDetails],
            ];
        }

        return [
            'counts' => ['used_count' => 1],
            'details' => ['disposition' => 'use_now', 'used_item' => $this->removedItemDetails($itemDetails, 'used')],
        ];
    }

    /**
     * Sends one Server Message per alchemy item name summarizing how many were
     * used on the character and how many were kept in the Alchemy Bag this tick,
     * instead of one message per individual item processed.
     */
    private function sendAlchemyUseNowAggregateMessages(Character $character, BatchCraftingDisposition $disposition, array $actions): void
    {
        if ($disposition !== BatchCraftingDisposition::USE_NOW) {
            return;
        }

        $summaries = [];

        foreach ($actions as $action) {
            if (($action['action'] ?? null) !== 'alchemy') {
                continue;
            }

            $usedItemName = $action['used_item']['name'] ?? null;
            $keptItemName = $action['kept_item']['name'] ?? null;

            if (! is_null($usedItemName)) {
                $summaries[$usedItemName]['used'] = ($summaries[$usedItemName]['used'] ?? 0) + 1;
                $summaries[$usedItemName]['kept'] = $summaries[$usedItemName]['kept'] ?? 0;
                $summaries[$usedItemName]['slot_id'] = $summaries[$usedItemName]['slot_id'] ?? null;

                continue;
            }

            if (! is_null($keptItemName)) {
                $summaries[$keptItemName]['kept'] = ($summaries[$keptItemName]['kept'] ?? 0) + 1;
                $summaries[$keptItemName]['used'] = $summaries[$keptItemName]['used'] ?? 0;
                $summaries[$keptItemName]['slot_id'] = $action['kept_item']['slot_id'] ?? null;
            }
        }

        foreach ($summaries as $itemName => $summary) {
            $message = $this->buildAlchemyUseNowAggregateMessage($itemName, $summary['used'], $summary['kept']);

            if ($summary['kept'] > 0 && ! is_null($summary['slot_id'] ?? null)) {
                $this->serverMessageHandler->sendBasicMessageWithLink(
                    $character->user,
                    $message,
                    $summary['slot_id'],
                    'alchemy_bag',
                    $itemName,
                );

                continue;
            }

            $this->serverMessageHandler->sendBasicMessage($character->user, $message);
        }
    }

    private function buildAlchemyUseNowAggregateMessage(string $itemName, int $usedCount, int $keptCount): string
    {
        if ($usedCount > 0 && $keptCount > 0) {
            return 'Used ' . $usedCount . ' ' . $itemName . ' ' . ($usedCount === 1 ? 'boon' : 'boons') . ' on you. Kept ' . $keptCount . ' extra ' . $itemName . ' in your Alchemy Bag.';
        }

        if ($usedCount > 0) {
            return 'Used ' . $usedCount . ' ' . $itemName . ' ' . ($usedCount === 1 ? 'boon' : 'boons') . ' on you.';
        }

        return 'Kept ' . $keptCount . ' ' . $itemName . ' in your Alchemy Bag because it could not be used on you right now.';
    }

    private function applyAlchemyKeepHighest(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot): array
    {
        $progress = $batchCrafting->progress ?? [];
        $trackedSlotId = $progress['alchemy_keep_highest_slot'] ?? null;

        if (is_null($trackedSlotId)) {
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)],
            ];
        }

        $trackedSlot = AlchemyBagSlot::where('character_id', $character->id)->find($trackedSlotId);

        if (is_null($trackedSlot) || is_null($trackedSlot->item)) {
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false)],
            ];
        }

        $newLevel = (int) ($slot->item->skill_level_required ?? 0);
        $trackedLevel = (int) ($trackedSlot->item->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $sold = $this->sellAlchemySlot($character, $trackedSlot);
            $batchCrafting->update(['progress' => array_merge($progress, ['alchemy_keep_highest_slot' => $slot->id])]);

            return [
                'counts' => ['kept_count' => 1, 'sold_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($slot->item, $slot->id, false), 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
            ];
        }

        $sold = $this->sellAlchemySlot($character, $slot);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'keep_highest', 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
        ];
    }

    private function processHolyOils(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['holy_oil_mode'] ?? 'selected') === 'set') {
            return $this->processHolyOilsSetSingle($batchCrafting, $character, $disposition);
        }

        $requestedApplications = max(1, count($batchCrafting->selected_items ?? []) * count($batchCrafting->selected_oils ?? []));
        $progress['holy_oil_requested_applications'] = $progress['holy_oil_requested_applications'] ?? $requestedApplications;
        $batchCrafting->update(['progress' => $progress]);

        $result = $this->processHolyOilSingle($batchCrafting, $character, $disposition);

        $updatedProgress = $batchCrafting->refresh()->progress ?? [];

        if (($updatedProgress['all_oils_applied'] ?? false) === true && ! isset($result['end_reason'])) {
            $result['end_reason'] = BatchCraftingEndReason::ALL_OILS_APPLIED;
        }

        return $result;
    }

    private function processHolyOilSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $applicationPlan = $progress['holy_oil_application_plan']['application_sequence'] ?? [];
        $planIndex = (int) ($progress['holy_oil_plan_index'] ?? 0);
        $plannedApplication = $applicationPlan[$planIndex] ?? null;

        if (! is_array($plannedApplication)) {
            $progress['all_oils_applied'] = true;
            $batchCrafting->update(['progress' => $progress]);

            return ['end_reason' => BatchCraftingEndReason::ALL_OILS_APPLIED];
        }

        $selectedItems = $batchCrafting->selected_items ?? [];
        $selectedOils = $batchCrafting->selected_oils ?? [];

        $inventory = $character->inventory;

        $selectedItems = array_values(array_filter($selectedItems, function (int $slotId) use ($inventory) {
            if (is_null($inventory)) {
                return false;
            }

            $slot = $inventory->slots()->where('id', $slotId)->first();

            if (is_null($slot) || is_null($slot->item)) {
                return false;
            }

            return ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0;
        }));

        $selectedOils = array_values(array_filter($selectedOils, function (int $oilSlotId) use ($character) {
            return AlchemyBagSlot::where('id', $oilSlotId)
                ->where('character_id', $character->id)
                ->where('amount', '>', 0)
                ->exists();
        }));

        if (empty($selectedItems)) {
            $batchCrafting->update(['selected_items' => $selectedItems, 'selected_oils' => $selectedOils]);

            return ['end_reason' => BatchCraftingEndReason::NO_SELECTED_ITEMS_LEFT];
        }

        if (empty($selectedOils)) {
            $batchCrafting->update(['selected_items' => $selectedItems, 'selected_oils' => $selectedOils]);

            return ['end_reason' => BatchCraftingEndReason::NO_OILS_LEFT];
        }

        $slotId = (int) $plannedApplication['target_slot_id'];
        $oilSlotId = (int) $plannedApplication['oil_slot_id'];

        $targetSlot = is_null($inventory) ? null : $inventory->slots()->where('id', $slotId)->with('item')->first();
        $oilSlot = AlchemyBagSlot::where('id', $oilSlotId)->where('character_id', $character->id)->with('item')->first();
        $oilCost = 0;

        if (! is_null($targetSlot) && ! is_null($targetSlot->item) && ! is_null($oilSlot) && ! is_null($oilSlot->item)) {
            $oilCost = $this->holyItemService->getCost($targetSlot->item, $oilSlot->item);

            if ($oilCost > $character->gold_dust) {
                return ['end_reason' => BatchCraftingEndReason::NO_GOLD_DUST];
            }
        }

        $targetItemSnapshot = is_null($targetSlot) ? null : $this->itemDetails($targetSlot->item, $targetSlot->id);
        $oilItemSnapshot = is_null($oilSlot) ? null : $this->itemDetails($oilSlot->item, $oilSlot->id, false);
        $targetItemId = $targetSlot?->item_id;

        $existingSlotIds = is_null($inventory) ? [] : $inventory->slots()->pluck('id')->all();

        $result = $this->holyItemService->applyOil($character, [
            'inventory_slot_id' => $slotId,
            'alchemy_slot_id' => $oilSlotId,
        ]);

        if (! isset($result['status']) || $result['status'] !== 200) {
            $batchCrafting->update(['selected_items' => $selectedItems, 'selected_oils' => $selectedOils]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'holy_oil',
                'failure' => 'Holy oil application failed.',
                'oil_application' => [
                    'target_item' => $targetItemSnapshot,
                    'oil_item' => $oilItemSnapshot,
                    'item_id' => $targetItemId,
                    'oil_slot_id' => $oilSlotId,
                ],
            ]]];
        }

        $character = $character->refresh();

        $originalSlotStillExists = ! is_null($character->inventory)
            && $character->inventory->slots()->where('id', $slotId)->exists();

        $saturatedSlotId = null;
        $replacementSlotId = null;

        if (! $originalSlotStillExists) {
            $newSlot = $character->inventory?->slots()->whereNotIn('id', $existingSlotIds)->first();

            if (! is_null($newSlot) && ! is_null($newSlot->item) && ($newSlot->item->holy_stacks - $newSlot->item->holy_stacks_applied) > 0) {
                $newSlotId = $newSlot->id;
                $replacementSlotId = $newSlotId;
                $selectedItems = array_map(fn (int $id) => $id === $slotId ? $newSlotId : $id, $selectedItems);
            } else {
                $saturatedSlotId = $newSlot?->id;
                $selectedItems = array_values(array_filter($selectedItems, fn (int $id) => $id !== $slotId));
            }
        } else {
            $stillEligible = $character->inventory->slots()->where('id', $slotId)
                ->whereHas('item', fn ($query) => $query->whereColumn('holy_stacks', '>', 'holy_stacks_applied'))
                ->exists();

            if (! $stillEligible) {
                $saturatedSlotId = $slotId;
                $selectedItems = array_values(array_filter($selectedItems, fn (int $id) => $id !== $slotId));
            }
        }

        $oilStillExists = AlchemyBagSlot::where('id', $oilSlotId)
            ->where('character_id', $character->id)
            ->where('amount', '>', 0)
            ->exists();

        if (! $oilStillExists) {
            $selectedOils = array_values(array_filter($selectedOils, fn (int $id) => $id !== $oilSlotId));
        }

        $batchCrafting->update(['selected_items' => $selectedItems, 'selected_oils' => $selectedOils]);

        $progress = $batchCrafting->progress ?? [];
        $progress['holy_oil_completed_applications'] = ((int) ($progress['holy_oil_completed_applications'] ?? 0)) + 1;
        if (is_array($plannedApplication)) {
            $progress['holy_oil_plan_index'] = $planIndex + 1;

            if (! is_null($replacementSlotId)) {
                foreach ($progress['holy_oil_application_plan']['application_sequence'] as $applicationIndex => $application) {
                    if ($applicationIndex > $planIndex && (int) $application['target_slot_id'] === $slotId) {
                        $progress['holy_oil_application_plan']['application_sequence'][$applicationIndex]['target_slot_id'] = $replacementSlotId;
                    }
                }
            }
        }
        $progress['holy_oil_gold_dust_spent'] = ((int) ($progress['holy_oil_gold_dust_spent'] ?? 0)) + $oilCost;
        $progress['holy_oil_current_target_item'] = $targetItemSnapshot;
        $progress['holy_oil_current_oil_item'] = $oilItemSnapshot;
        $resultSlotId = $replacementSlotId ?? $saturatedSlotId ?? $slotId;
        $resultSlot = $character->inventory?->slots()->where('id', $resultSlotId)->with('item')->first();
        $progress = $this->recordHolyOilApplicationResult(
            $progress,
            $slotId,
            $resultSlotId,
            $resultSlot?->item,
            (int) ($targetItemSnapshot['holy_stacks_applied'] ?? 0),
            $oilCost,
        );

        if (empty($selectedOils)) {
            $progress['all_oils_applied'] = true;
        }

        $batchCrafting->update(['progress' => $progress]);

        $disposalResult = $disposition === BatchCraftingDisposition::KEEP
            ? null
            : $this->disposeHolyOilInventorySlot($batchCrafting, $character, $saturatedSlotId, $disposition);

        return ['counts' => array_merge(['applied_count' => 1], $disposalResult['counts'] ?? []), 'actions' => [array_merge([
            'action' => 'holy_oil',
            'oil_application' => [
                'target_item' => $targetItemSnapshot,
                'oil_item' => $oilItemSnapshot,
                'item_id' => $targetItemId,
                'oil_slot_id' => $oilSlotId,
            ],
        ], $disposalResult['details'] ?? [])]];
    }

    /**
     * Disposition applies once the Holy Oil target item is fully saturated (no
     * remaining stacks to apply), not after every individual oil application, so a
     * partially-oiled item is never sold/destroyed/listed/disenchanted mid-stack.
     */
    private function disposeHolyOilInventorySlot(BatchCrafting $batchCrafting, Character $character, ?int $slotId, BatchCraftingDisposition $disposition): ?array
    {
        if (is_null($slotId)) {
            return null;
        }

        $itemDetails = $this->itemDetails($character->inventory?->slots()->where('id', $slotId)->with('item')->first()?->item, $slotId);

        return match ($disposition) {
            BatchCraftingDisposition::SELL => $this->sellHolyOilInventorySlot($character, $slotId, $itemDetails),
            BatchCraftingDisposition::DESTROY => $this->destroyHolyOilInventorySlot($character, $slotId, $itemDetails),
            BatchCraftingDisposition::DISENCHANT => $this->disenchantHolyOilInventorySlot($character, $slotId, $itemDetails),
            BatchCraftingDisposition::LIST => $this->listHolyOilInventorySlot($batchCrafting, $character, $slotId, $itemDetails),
            default => null,
        };
    }

    private function sellHolyOilInventorySlot(Character $character, int $slotId, ?array $itemDetails): array
    {
        $goldBefore = $character->gold;
        $this->multiInventoryActionService->sellManyItems($character, [$slotId]);
        $goldGained = max(0, $character->refresh()->gold - $goldBefore);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyHolyOilInventorySlot(Character $character, int $slotId, ?array $itemDetails): array
    {
        $this->multiInventoryActionService->destroyManyItems($character, [$slotId]);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Destroyed: ' . ($itemDetails['name'] ?? 'item') . '.');

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function disenchantHolyOilInventorySlot(Character $character, int $slotId, ?array $itemDetails): array
    {
        $goldDustBefore = $character->gold_dust;
        $this->multiInventoryActionService->disenchantManyItems($character, [$slotId]);
        $goldDustGained = max(0, $character->refresh()->gold_dust - $goldDustBefore);

        return [
            'counts' => ['disenchanted_count' => 1],
            'details' => ['disposition' => 'disenchant', 'disenchanted_item' => $this->removedItemDetails($itemDetails, 'disenchanted'), 'gold_dust_gained' => $goldDustGained],
        ];
    }

    private function listHolyOilInventorySlot(BatchCrafting $batchCrafting, Character $character, int $slotId, ?array $itemDetails): array
    {
        $slot = $character->inventory?->slots()->where('id', $slotId)->with('item')->first();

        if (is_null($slot) || is_null($slot->item)) {
            return ['counts' => ['failed_count' => 1], 'details' => ['disposition' => 'list', 'failure' => 'Item could not be listed.']];
        }

        $requestedPrice = (int) (($batchCrafting->progress ?? [])['listing_price'] ?? 1);
        $minPrice = (int) SellItemCalculator::fetchMinPrice($slot->item);
        $price = max($requestedPrice, $minPrice, 1);

        MarketBoard::create([
            'character_id' => $character->id,
            'item_id' => $slot->item_id,
            'listed_price' => $price,
        ]);

        $slot->delete();

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Listed: ' . ($itemDetails['name'] ?? 'item') . ' for: ' . number_format($price) . ' Gold.');

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($itemDetails, 'listed'), 'listed_price' => $price],
        ];
    }

    private function processHolyOilsSetSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $set = InventorySet::where('id', $progress['selected_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set) || $set->isBatchCraftingSet()) {
            return ['end_reason' => BatchCraftingEndReason::NO_SELECTED_ITEMS_LEFT];
        }

        $applicationPlan = $progress['holy_oil_application_plan']['application_sequence'] ?? [];
        $planIndex = (int) ($progress['holy_oil_plan_index'] ?? 0);
        $plannedApplication = $applicationPlan[$planIndex] ?? null;

        if (! is_array($plannedApplication)) {
            $progress['all_oils_applied'] = true;
            $batchCrafting->update(['progress' => $progress]);

            return ['end_reason' => BatchCraftingEndReason::ALL_OILS_APPLIED];
        }

        $selectedOils = array_values(array_filter($batchCrafting->selected_oils ?? [], function (int $oilSlotId) use ($character) {
            return AlchemyBagSlot::where('id', $oilSlotId)
                ->where('character_id', $character->id)
                ->where('amount', '>', 0)
                ->exists();
        }));

        if (empty($selectedOils)) {
            $batchCrafting->update(['selected_oils' => $selectedOils]);

            return ['end_reason' => BatchCraftingEndReason::NO_OILS_LEFT];
        }

        $eligibleSlot = $set->slots()
            ->where('id', (int) $plannedApplication['target_slot_id'])
            ->with('item.appliedHolyStacks')
            ->first();

        if (is_null($eligibleSlot)) {
            $progress['all_oils_applied'] = true;
            $batchCrafting->update(['progress' => $progress]);

            return ['end_reason' => BatchCraftingEndReason::ALL_OILS_APPLIED];
        }

        $oilSlotId = (int) $plannedApplication['oil_slot_id'];
        $oilSlot = AlchemyBagSlot::where('id', $oilSlotId)->where('character_id', $character->id)->with('item')->first();

        if (is_null($oilSlot) || is_null($oilSlot->item)) {
            $selectedOils = array_values(array_filter($selectedOils, fn (int $id) => $id !== $oilSlotId));
            $batchCrafting->update(['selected_oils' => $selectedOils]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'holy_oil_set',
                'status' => 'skipped',
                'failure' => 'Selected oil is no longer available.',
            ]]];
        }

        $cost = $this->holyItemService->getCost($eligibleSlot->item, $oilSlot->item);

        if ($cost > $character->gold_dust) {
            return ['end_reason' => BatchCraftingEndReason::NO_GOLD_DUST];
        }

        $targetItemSnapshot = $this->itemDetails($eligibleSlot->item, null, false);
        $oilItemSnapshot = $this->itemDetails($oilSlot->item, null, false);

        $character->update(['gold_dust' => $character->gold_dust - $cost]);
        $application = $this->applyHolyOilToSetSlot($eligibleSlot, $oilSlot);
        $newSlot = $application['slot'];

        $oilStillExists = AlchemyBagSlot::where('id', $oilSlotId)
            ->where('character_id', $character->id)
            ->where('amount', '>', 0)
            ->exists();

        if (! $oilStillExists) {
            $selectedOils = array_values(array_filter($selectedOils, fn (int $id) => $id !== $oilSlotId));
            $batchCrafting->update(['selected_oils' => $selectedOils]);
        }

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['holy_oil_completed_applications'] = ((int) ($progress['holy_oil_completed_applications'] ?? 0)) + 1;
        if (is_array($plannedApplication)) {
            $progress['holy_oil_plan_index'] = $planIndex + 1;
        }
        $progress['holy_oil_gold_dust_spent'] = ((int) ($progress['holy_oil_gold_dust_spent'] ?? 0)) + $cost;
        $progress['holy_oil_total_stat_bonus_applied'] = ((float) ($progress['holy_oil_total_stat_bonus_applied'] ?? 0)) + $application['stat_increase_bonus'];
        $progress['holy_oil_total_devouring_darkness_bonus_applied'] = ((float) ($progress['holy_oil_total_devouring_darkness_bonus_applied'] ?? 0)) + $application['devouring_darkness_bonus'];
        $progress['holy_oil_current_target_item'] = $targetItemSnapshot;
        $progress['holy_oil_current_oil_item'] = $oilItemSnapshot;
        $refreshedResultSlot = $newSlot->fresh('item');
        $progress = $this->recordHolyOilApplicationResult(
            $progress,
            $eligibleSlot->id,
            $newSlot->id,
            $refreshedResultSlot?->item,
            (int) ($targetItemSnapshot['holy_stacks_applied'] ?? 0),
            $cost,
        );
        $batchCrafting->update(['progress' => $progress]);

        $refreshedSlot = $refreshedResultSlot;
        $stillEligible = ! is_null($refreshedSlot) && ! is_null($refreshedSlot->item)
            && ($refreshedSlot->item->holy_stacks - $refreshedSlot->item->holy_stacks_applied) > 0;
        $saturatedSetSlotId = $stillEligible ? null : $newSlot->id;

        $disposalResult = $disposition === BatchCraftingDisposition::KEEP
            ? null
            : $this->disposeHolyOilSetSlot($batchCrafting, $character, $set, $saturatedSetSlotId, $disposition);

        return ['counts' => array_merge(['applied_count' => 1], $disposalResult['counts'] ?? []), 'actions' => [array_merge([
            'action' => 'holy_oil_set',
            'oil_application' => [
                'target_item' => $targetItemSnapshot,
                'oil_item' => $oilItemSnapshot,
                'set_slot_id' => $newSlot->id,
                'oil_slot_id' => $oilSlotId,
            ],
        ], $disposalResult['details'] ?? [])]];
    }

    private function disposeHolyOilSetSlot(BatchCrafting $batchCrafting, Character $character, InventorySet $set, ?int $setSlotId, BatchCraftingDisposition $disposition): ?array
    {
        if (is_null($setSlotId)) {
            return null;
        }

        $itemDetails = $this->itemDetails($set->slots()->where('id', $setSlotId)->with('item')->first()?->item);

        return match ($disposition) {
            BatchCraftingDisposition::SELL => $this->sellHolyOilSetSlot($character, $set, $setSlotId, $itemDetails),
            BatchCraftingDisposition::DESTROY => $this->destroyHolyOilSetSlot($character, $set, $setSlotId, $itemDetails),
            BatchCraftingDisposition::DISENCHANT => $this->disenchantHolyOilSetSlot($character, $set, $setSlotId, $itemDetails),
            BatchCraftingDisposition::LIST => $this->listHolyOilSetSlot($batchCrafting, $character, $set, $setSlotId, $itemDetails),
            default => null,
        };
    }

    private function recordHolyOilApplicationResult(
        array $progress,
        int $previousSlotId,
        int $resultSlotId,
        ?Item $item,
        int $initialStackCount,
        int $goldDustSpent,
    ): array {
        if (is_null($item)) {
            return $progress;
        }

        $results = array_values($progress['holy_oil_application_results'] ?? []);
        $resultIndex = null;

        foreach ($results as $index => $result) {
            if (in_array((int) ($result['target_slot_id'] ?? 0), [$previousSlotId, $resultSlotId], true)) {
                $resultIndex = $index;
                break;
            }
        }

        $existingResult = is_null($resultIndex) ? null : $results[$resultIndex];
        $result = [
            'target_slot_id' => $resultSlotId,
            'item' => $this->itemDetails($item, $resultSlotId),
            'initial_stack_count' => (int) ($existingResult['initial_stack_count'] ?? $initialStackCount),
            'actual_applications_completed' => ((int) ($existingResult['actual_applications_completed'] ?? 0)) + 1,
            'actual_resulting_stack_count' => (int) ($item->holy_stacks_applied ?? 0),
            'maximum_stacks' => (int) ($item->holy_stacks ?? 0),
            'actual_gold_dust_spent' => ((int) ($existingResult['actual_gold_dust_spent'] ?? 0)) + $goldDustSpent,
            'oil_applications_consumed' => ((int) ($existingResult['oil_applications_consumed'] ?? 0)) + 1,
        ];

        if (is_null($resultIndex)) {
            $results[] = $result;
        } else {
            $results[$resultIndex] = $result;
        }

        $progress['holy_oil_application_results'] = $results;

        return $progress;
    }

    private function sellHolyOilSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $goldBefore = $character->gold;
        $this->multiInventoryActionService->sellManySetSlots($character, $set, [$setSlotId]);
        $goldGained = max(0, $character->refresh()->gold - $goldBefore);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyHolyOilSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $this->multiInventoryActionService->destroyManySetSlots($character, $set, [$setSlotId]);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Destroyed: ' . ($itemDetails['name'] ?? 'item') . '.');

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function disenchantHolyOilSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $goldDustBefore = $character->gold_dust;
        $this->multiInventoryActionService->disenchantManySetSlots($character, $set, [$setSlotId]);
        $goldDustGained = max(0, $character->refresh()->gold_dust - $goldDustBefore);

        return [
            'counts' => ['disenchanted_count' => 1],
            'details' => ['disposition' => 'disenchant', 'disenchanted_item' => $this->removedItemDetails($itemDetails, 'disenchanted'), 'gold_dust_gained' => $goldDustGained],
        ];
    }

    private function listHolyOilSetSlot(BatchCrafting $batchCrafting, Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $slot = $set->slots()->where('id', $setSlotId)->with('item')->first();

        if (is_null($slot) || is_null($slot->item)) {
            return ['counts' => ['failed_count' => 1], 'details' => ['disposition' => 'list', 'failure' => 'Item could not be listed.']];
        }

        $requestedPrice = (int) (($batchCrafting->progress ?? [])['listing_price'] ?? 1);
        $minPrice = (int) SellItemCalculator::fetchMinPrice($slot->item);
        $price = max($requestedPrice, $minPrice, 1);

        $this->multiInventoryActionService->listManySetSlots($character, $set, [$setSlotId], $price);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Listed: ' . ($itemDetails['name'] ?? 'item') . ' for: ' . number_format($price) . ' Gold.');

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($itemDetails, 'listed'), 'listed_price' => $price],
        ];
    }

    private function applyHolyOilToSetSlot(SetSlot $setSlot, AlchemyBagSlot $oilSlot): array
    {
        $holyItemEffect = new ItemHolyValue($oilSlot->item->holy_level);
        $devouringDarknessBonus = $holyItemEffect->getRandomDevoidanceIncrease();
        $statIncreaseBonus = $holyItemEffect->getRandomStatIncrease() / 100;

        if ($setSlot->item->appliedHolyStacks->isEmpty()) {
            $newItem = $setSlot->item->duplicate();

            $newItem->update([
                'market_sellable' => true,
                'is_mythic' => $setSlot->item->is_mythic,
                'is_cosmic' => $setSlot->item->is_cosmic,
            ]);

            $newItem->appliedHolyStacks()->create([
                'item_id' => $newItem->id,
                'devouring_darkness_bonus' => $devouringDarknessBonus,
                'stat_increase_bonus' => $statIncreaseBonus,
            ]);

            $setSlot->update(['item_id' => $newItem->id]);
            $this->decrementAlchemySlot($oilSlot);

            return [
                'slot' => $setSlot->refresh(),
                'devouring_darkness_bonus' => $devouringDarknessBonus,
                'stat_increase_bonus' => $statIncreaseBonus,
            ];
        }

        $this->decrementAlchemySlot($oilSlot);

        $setSlot->item->appliedHolyStacks()->create([
            'item_id' => $setSlot->item_id,
            'devouring_darkness_bonus' => $devouringDarknessBonus,
            'stat_increase_bonus' => $statIncreaseBonus,
        ]);

        return [
            'slot' => $setSlot->refresh(),
            'devouring_darkness_bonus' => $devouringDarknessBonus,
            'stat_increase_bonus' => $statIncreaseBonus,
        ];
    }

    private function processTrinketry(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $requiredCapacity = $disposition === BatchCraftingDisposition::KEEP ? BatchCraftingService::ITEMS_PER_RECURRING_TICK : 1;

        if ($this->shouldCommitKeptOutput($disposition)) {
            $capacityEndReason = $this->retainedOutputCapacityEndReason($batchCrafting, $character, $requiredCapacity);

            if (! is_null($capacityEndReason)) {
                return ['end_reason' => $capacityEndReason];
            }
        }

        return $this->processRepeatedActions(BatchCraftingService::ITEMS_PER_RECURRING_TICK, function () use ($batchCrafting, $character, $disposition) {
            return $this->processTrinketrySingle($batchCrafting->refresh(), $character->refresh(), $disposition);
        });
    }

    private function processTrinketrySingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $trinketryMode = $progress['trinketry_mode'] ?? 'experience';

        if ($trinketryMode === 'experience') {
            $skill = $character->skills()
                ->whereHas('baseSkill', fn ($q) => $q->where('name', 'Trinketry'))
                ->with('baseSkill')
                ->first();

            if (! is_null($skill) && $skill->level >= $skill->max_level) {
                return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
            }
        }

        $items = $this->trinketCraftingService->fetchItemsToCraft($character, false);

        if (empty($items)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $item = $this->xpEligibleItemFromCandidates(collect($items), $skill?->level);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $destinationCreator = $this->shouldCommitKeptOutput($disposition)
            ? $this->resolvedDestinationCreator($batchCrafting, $character)
            : null;
        $craftResult = $this->trinketCraftingService->craftForBatch(
            $character,
            $item,
            $this->shouldCommitKeptOutput($disposition),
            $destinationCreator,
        );

        if (($craftResult['success'] ?? false) && ! is_null($destinationCreator) && is_null($craftResult['destination'] ?? null)) {
            if (isset($craftResult['item']) && $craftResult['item'] instanceof Item) {
                $this->discardPartialBatchItem($craftResult['item']);
            }

            $craftResult = ['success' => false, 'item' => null, 'reason' => 'destination_failed', 'destination' => null];
        }
        $character = $character->refresh();

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            $result = ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'trinketry',
                'status' => 'failed',
                'failure' => 'Trinketry service did not produce an item.',
            ]]];

            if (($craftResult['reason'] ?? null) === 'destination_failed') {
                $result['end_reason'] = BatchCraftingEndReason::FAILED;
            }

            return $result;
        }

        $craftedItem = $craftResult['item'];
        $dispositionResult = $this->dispositionForCraftResult(
            $batchCrafting,
            $character,
            $craftedItem,
            $disposition,
            $craftResult['destination'] ?? null,
        );
        $counts = $this->mergeCounts(['crafted_count' => 1], $dispositionResult['counts']);
        $actions = [[
            'action' => 'trinketry',
            'trinketry_item' => $this->itemDetails($craftedItem),
        ] + $dispositionResult['details']];

        $result = [
            'counts' => $counts,
            'actions' => $actions,
            'kept_output_committed' => $this->shouldCommitKeptOutput($disposition),
        ];

        return $result;
    }

    private function processCraftExperience(
        BatchCrafting $batchCrafting,
        Character $character,
        BatchCraftingDisposition $disposition,
        array $progress,
        bool $moveKeptOutputToBatchSet = false,
        bool $craftOnly = false,
    ): array
    {
        $isCraftAndEnchant = $batchCrafting->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value;

        if (! $isCraftAndEnchant && $this->allCraftingSkillsMaxed($character)) {
            return ['end_reason' => BatchCraftingEndReason::SKILL_MAXED];
        }

        $queue = $progress['craft_experience_queue'] ?? null;

        if (is_null($queue)) {
            $targets = in_array($batchCrafting->batch_type, [BatchCraftingType::CRAFT->value, BatchCraftingType::CRAFT_AND_ENCHANT->value], true)
                ? $this->nonMaxedCraftExperienceTargets($character)
                : $this->craftExperienceTargets($progress['craft_experience_skill'] ?? null);

            $allCraftingSkillsMaxedFallback = $isCraftAndEnchant && empty($targets);

            if ($allCraftingSkillsMaxedFallback) {
                $targets = $this->craftExperienceTargets(null);
            }

            $queue = $targets;
            $hasEligibleTarget = false;

            foreach ($targets as $targetToCheck) {
                $hasEligibleTarget = $allCraftingSkillsMaxedFallback
                    ? ! is_null($this->craftableItemForTarget($character, $targetToCheck['type'], $targetToCheck['crafting_type']))
                    : ! is_null($this->xpEligibleCraftableItem($character, $targetToCheck['crafting_type'], $targetToCheck['type']));

                if ($hasEligibleTarget) {
                    break;
                }
            }

            $progress['craft_experience_queue'] = $queue;
            $progress['craft_experience_index'] = 0;
            $progress['craft_experience_has_targets'] = $hasEligibleTarget;
            $progress['craft_experience_ignore_xp_eligibility'] = $allCraftingSkillsMaxedFallback;
            $batchCrafting->update(['progress' => $progress]);
        }

        $index = (int) ($progress['craft_experience_index'] ?? 0);

        if (empty($queue)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if (($progress['craft_experience_has_targets'] ?? true) === false) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        if ($index >= count($queue)) {
            $index = 0;
            $progress['craft_experience_index'] = 0;
            $batchCrafting->update(['progress' => $progress]);
        }

        $ignoreXpEligibility = ($progress['craft_experience_ignore_xp_eligibility'] ?? false) === true;
        $queueLength = count($queue);
        $attemptsRemaining = $queueLength;
        $target = $queue[$index];
        $craftingType = $target['crafting_type'];
        $item = null;

        while ($attemptsRemaining > 0) {
            $target = $queue[$index];
            $craftingType = $target['crafting_type'];
            $item = $ignoreXpEligibility
                ? $this->craftableItemForTarget($character, $target['type'], $craftingType)
                : $this->xpEligibleCraftableItem($character, $craftingType, $target['type']);

            $index = ($index + 1) % $queueLength;
            $attemptsRemaining--;

            if (! is_null($item)) {
                break;
            }
        }

        $progress['craft_experience_index'] = $index;
        $batchCrafting->update(['progress' => $progress]);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_experience_current_item_snapshot'] = $this->itemDetails($item, null, false);
        $progress['craft_experience_current_type'] = $craftingType;
        $batchCrafting->update(['progress' => $progress]);

        $shouldMoveKeptOutputToBatchSet = $moveKeptOutputToBatchSet && $this->shouldCommitKeptOutput($disposition);

        if ($craftOnly) {
            $craftResult = $this->craftingService->craftForBatch($character->refresh(), $item, $craftingType);

            if (! $craftResult['success'] || is_null($craftResult['item'])) {
                return ['counts' => ['failed_count' => 1], 'actions' => [[
                    'action' => 'craft',
                    'failure' => 'Crafting attempt failed. No item was produced.',
                ]]];
            }

            return ['counts' => ['crafted_count' => 1], 'actions' => [[
                'action' => 'craft',
                'crafted_item' => $this->itemDetails($craftResult['item']),
            ]]];
        }

        $result = $this->craftItem($batchCrafting, $character, $disposition, $item, $craftingType, $shouldMoveKeptOutputToBatchSet);

        return $result;
    }

    private function processCraftAndEnchantExperience(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        // Step 1: the forced-KEEP call below only exists to obtain an item to enchant.
        // Its kept-state is intermediate and must never leak into the final result.
        $retainedOutput = $this->shouldCommitKeptOutput($disposition);
        $result = $this->processCraftExperience(
            $batchCrafting,
            $character,
            BatchCraftingDisposition::KEEP,
            $progress,
            false,
            ! $retainedOutput,
        );
        $destination = $this->destinationFromResult($result);
        $this->removeIntermediateKeptOutput($result, 0);

        if (isset($result['actions'][0])) {
            $result['actions'][0]['action'] = 'craft_and_enchant';
        }

        $itemId = $result['actions'][0]['crafted_item']['item_id'] ?? null;

        // Step 2: no crafted item to enchant — nothing further to do.
        if (is_null($itemId)) {
            return $result;
        }

        $item = Item::find($itemId);

        // Step 2b: the crafted item row is gone before enchanting could even be
        // attempted — report it as a failed attempt instead of silently doing nothing.
        if (is_null($item)) {
            $this->removeDestinationItem($destination);
            $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['failed_count' => 1]);
            $result['actions'][0]['status'] = 'failed';
            $result['actions'][0]['failure'] = 'The crafted item could not be found before enchanting. Another item will be crafted on a later attempt.';

            return $result;
        }

        $resolvedAffixIds = $this->resolveIntendedAffixIdsForEnchant($character->refresh(), $progress['enchant_affix_ids'] ?? null);
        $resolvedAffixes = ItemAffix::whereIn('id', $resolvedAffixIds)->get();
        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_experience_current_prefix_affix'] = $resolvedAffixes->firstWhere('type', 'prefix')?->toArray();
        $progress['craft_experience_current_suffix_affix'] = $resolvedAffixes->firstWhere('type', 'suffix')?->toArray();
        $batchCrafting->update(['progress' => $progress]);

        // Step 3: INT-blocked is a terminal stop for this attempt — discard the crafted
        // item, apply no disposition, attempt no commit.
        if ($this->intBlockedForEnchant($character->refresh(), $resolvedAffixIds)) {
            $this->removeDestinationItem($destination);
            $this->discardBatchClonedItemIfOrphaned($item);
            $result['end_reason'] = BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING;
            $result['int_stop_affix_ids'] = $resolvedAffixIds;
            $result['actions'][0]['status'] = 'stopped';
            $result['actions'][0]['failure'] = 'Your Intelligence is too low for the selected enchantment.';

            return $result;
        }

        $suppressEnchantSuccessMessage = $this->shouldCommitKeptOutput($disposition);
        $enchantAttempt = $this->tryEnchantItem($character->refresh(), $item, $resolvedAffixIds, $suppressEnchantSuccessMessage);

        // Step 4: enchanting was not attempted — the unenchanted item is discarded, no
        // disposition is applied, and no commit is attempted.
        if (! $enchantAttempt['attempted']) {
            $this->removeDestinationItem($destination);
            $this->discardBatchClonedItemIfOrphaned($item);
            $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['failed_count' => 1]);
            $result['actions'][0]['status'] = 'failed';
            $result['actions'][0]['failure'] = 'Enchanting was not attempted. The unenchanted item was discarded and another item will be crafted on a later attempt.';

            return $result;
        }

        // Step 5: shattered while enchanting — destroyed, no disposition/commit.
        if (is_null($enchantAttempt['item'])) {
            $this->removeDestinationItem($destination);
            $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['destroyed_count' => 1]);
            $result['actions'][0]['status'] = 'destroyed';
            $result['actions'][0]['destroyed_item'] = $this->removedItemDetails($result['actions'][0]['crafted_item'] ?? null, 'destroyed');
            $result['actions'][0]['failure'] = 'The item shattered while enchanting and was destroyed.';

            return $result;
        }

        // Step 6: partial affix result — discard the item and require a replacement, no
        // disposition/commit.
        if (! $this->requestedAffixesSatisfied($enchantAttempt['item'], $enchantAttempt['affix_ids'])) {
            $this->removeDestinationItem($destination);
            $this->discardPartialBatchItem($enchantAttempt['item']);

            $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['failed_count' => 1]);
            $result['actions'][0]['status'] = 'partial_enchant_discarded';
            $result['actions'][0]['failure'] = 'The item did not receive every requested enchantment and was discarded. A replacement will be crafted.';

            return $result;
        }

        // Step 7: every requested affix landed — apply the real disposition exactly once.
        $enchantedItem = $enchantAttempt['item'];
        $this->replaceDestinationItem($destination, $enchantedItem);

        if (! $this->shouldCommitKeptOutput($disposition)) {
            $this->removeDestinationItem($destination);
        }

        $dispositionResult = $this->dispositionForCraftResult(
            $batchCrafting,
            $character->refresh(),
            $enchantedItem,
            $disposition,
            $destination,
        );
        $result['counts'] = $this->mergeCounts($result['counts'] ?? [], ['enchanted_count' => 1]);
        $result['counts'] = $this->mergeCounts($result['counts'], $dispositionResult['counts']);
        $result['actions'][0] = array_merge($result['actions'][0], [
            'enchanted_item' => $this->itemDetails($enchantedItem),
            'enchant_affix_name' => implode(', ', $enchantAttempt['affix_names']),
        ], $dispositionResult['details']);

        $result['kept_output_committed'] = $this->shouldCommitKeptOutput($disposition);

        return $result;
    }

    private function processCraftEnchantSetSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $phase = $progress['craft_enchant_set_phase'] ?? 'crafting';

        if ($phase === 'crafting') {
            return $this->craftEnchantSetCraftPhaseSingle($batchCrafting, $character, $disposition);
        }

        if ($phase === 'enchanting') {
            return $this->craftEnchantSetEnchantPhaseSingle($batchCrafting, $character, $disposition);
        }

        if ($phase === 'replacement_crafting') {
            return $this->craftEnchantSetReplacementCraftPhaseSingle($batchCrafting, $character, $disposition);
        }

        return ['end_reason' => BatchCraftingEndReason::FAILED];
    }

    private function craftEnchantSetEnchantExistingPhaseSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_enchant_set_queue'] ?? [];
        $keys = $progress['craft_enchant_set_keys'] ?? [];
        $index = (int) ($progress['craft_enchant_set_enchant_index'] ?? 0);

        if ($index >= count($queue)) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_ENCHANT_SET_COMPLETE];
        }

        $set = InventorySet::where('id', $progress['selected_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set) || $set->isBatchCraftingSet() || $set->is_equipped) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_ENCHANT_SET_TARGET_SET_CHANGED];
        }

        $key = $keys[$index] ?? (string) $index;
        $setSlotId = $progress['craft_enchant_set_existing_slot_ids'][$key] ?? null;

        if (is_null($setSlotId)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => []];
        }

        $setSlot = SetSlot::find($setSlotId);

        if (is_null($setSlot) || is_null($setSlot->item)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'skipped',
                'failure' => 'The item is no longer in the target set.',
            ]]];
        }

        if ($setSlot->item->is_unique || $setSlot->item->is_mythic || $setSlot->item->is_cosmic) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'skipped',
                'crafted_item' => $this->itemDetails($setSlot->item, null, false),
                'failure' => 'Uniques, Mythics and Cosmic items are never touched by batch enchanting.',
            ]]];
        }

        $beforeSnapshot = $this->itemDetails($setSlot->item, null, false);
        $plan = $progress['enchant_plan'][$key] ?? [];
        $prefixId = $plan['prefix_affix_id'] ?? null;
        $suffixId = $plan['suffix_affix_id'] ?? null;
        $prefixAffix = is_null($prefixId) ? null : ItemAffix::find($prefixId);
        $suffixAffix = is_null($suffixId) ? null : ItemAffix::find($suffixId);
        $affixIds = array_values(array_filter([$prefixId, $suffixId], fn ($affixId) => ! is_null($affixId)));

        if (empty($affixIds)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'crafted_item' => $beforeSnapshot,
                'failure' => 'This item has no prefix or suffix selected to enchant.',
            ]]];
        }

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'int_stop_affix_ids' => $affixIds, 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'stopped',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        if ($character->isInventoryFull()) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE];
        }

        if (! $this->inventorySetService->putItemFromInventorySetBackIntoCharacterInventory($character, $set, $setSlot->item)) {
            return ['end_reason' => BatchCraftingEndReason::NO_INVENTORY_SPACE];
        }

        $character = $character->refresh();
        $movedSlot = $character->inventory->slots()->where('item_id', $setSlot->item_id)->orderByDesc('id')->first();

        if (is_null($movedSlot)) {
            $progress['craft_enchant_set_enchant_index'] = $index + 1;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'skipped',
                'failure' => 'Item could not be moved out of the set for enchanting.',
            ]]];
        }

        $goldBeforeEnchant = $character->gold;
        $attempted = $this->tryEnchantSlot($character, $movedSlot, $affixIds);
        $goldSpent = max(0, $goldBeforeEnchant - $character->refresh()->gold);
        $survivingSlot = $attempted ? InventorySlot::find($movedSlot->id) : $movedSlot;

        $progress = $batchCrafting->fresh()->progress ?? [];
        $progress['craft_enchant_set_enchant_index'] = $index + 1;
        $progress['craft_enchant_set_completed_work_units'] = ((int) ($progress['craft_enchant_set_completed_work_units'] ?? 0)) + 1;

        if ($attempted && is_null($survivingSlot)) {
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['destroyed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'destroyed',
                'destroyed_item' => $this->removedItemDetails($beforeSnapshot, 'destroyed'),
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'gold_spent' => $goldSpent,
                'failure' => 'The item shattered while enchanting and was destroyed. It could not be returned to the set.',
            ]]];
        }

        $character = $character->refresh();
        $this->inventorySetService->assignItemToSet($set, $survivingSlot->refresh());

        $afterItem = $survivingSlot->refresh()->item;
        $prefixApplied = $attempted && ! is_null($prefixId) && $afterItem->item_prefix_id === $prefixId;
        $suffixApplied = $attempted && ! is_null($suffixId) && $afterItem->item_suffix_id === $suffixId;
        $enchantedSnapshot = $this->itemDetails($afterItem, null, false);

        if ($prefixApplied) {
            $progress['craft_enchant_set_prefix_applied_count'] = ((int) ($progress['craft_enchant_set_prefix_applied_count'] ?? 0)) + 1;
        }

        if ($suffixApplied) {
            $progress['craft_enchant_set_suffix_applied_count'] = ((int) ($progress['craft_enchant_set_suffix_applied_count'] ?? 0)) + 1;
        }

        $progress['craft_enchant_set_current_item'] = $enchantedSnapshot;
        $batchCrafting->update(['progress' => $progress]);

        if (! $prefixApplied && ! $suffixApplied) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'gold_spent' => $goldSpent,
                'failure' => 'Enchanting service did not apply either enchantment.',
            ]]];
        }

        return ['counts' => ['enchanted_count' => 1], 'actions' => [[
            'action' => 'craft_enchant_set_enchant',
            'phase' => 'enchanting',
            'status' => $prefixApplied && $suffixApplied ? 'double_enchanted' : 'enchanted',
            'crafted_item' => $beforeSnapshot,
            'enchanted_item' => $enchantedSnapshot,
            'prefix_affix_name' => $prefixAffix?->name,
            'suffix_affix_name' => $suffixAffix?->name,
            'prefix_applied' => $prefixApplied,
            'suffix_applied' => $suffixApplied,
            'gold_spent' => $goldSpent,
        ]]];
    }

    private function craftEnchantSetCraftPhaseSingle(
        BatchCrafting $batchCrafting,
        Character $character,
        BatchCraftingDisposition $disposition,
    ): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_enchant_set_queue'] ?? [];
        $keys = $progress['craft_enchant_set_keys'] ?? [];
        $index = (int) ($progress['craft_enchant_set_craft_index'] ?? 0);

        if ($this->shouldCommitKeptOutput($disposition)) {
            $capacityEndReason = $this->retainedOutputCapacityEndReason($batchCrafting, $character, 1);

            if (! is_null($capacityEndReason)) {
                return ['end_reason' => $capacityEndReason];
            }
        }

        if ($index >= count($queue)) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_ENCHANT_SET_COMPLETE];
        }

        $target = $queue[$index];
        $key = $keys[$index] ?? (string) $index;

        $selectedItemId = $progress['craft_enchant_set_selected_item_ids'][$key] ?? null;
        $item = is_null($selectedItemId)
            ? $this->highestCraftableItemForTarget($character, $target['type'], $target['crafting_type'])
            : $this->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type'])
                ->first(fn ($candidate) => (int) $candidate->id === (int) $selectedItemId);

        if (is_null($item)) {
            $targetLabel = $this->finiteSetTargetLabel($key);
            $progress['invalid_plan_message'] = 'Batch Crafting stopped because the selected item for ' . $targetLabel . ' is no longer available or craftable.';
            $batchCrafting->update(['progress' => $progress]);

            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, 'actions' => [[
                'action' => 'craft_enchant_set_craft',
                'phase' => 'crafting',
                'status' => 'stopped',
                'plan_key' => $key,
                'target_label' => $targetLabel,
                'failure' => $progress['invalid_plan_message'],
            ]]];
        }

        $goldBeforeCraft = $character->gold;
        $craftResult = $this->craftForResolvedDestination(
            $batchCrafting,
            $character->refresh(),
            $item,
            $target['crafting_type'],
            $disposition,
        );

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_craft',
                'phase' => 'crafting',
                'status' => 'failed',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];
        }

        $craftedItem = $craftResult['item'];
        $craftedItemSnapshot = $this->itemDetails($craftedItem, null, false);
        $goldSpent = max(0, $goldBeforeCraft - $character->refresh()->gold);

        $progress = $batchCrafting->fresh()->progress ?? [];
        $countedKeys = array_values(array_unique(array_map('strval', $progress['craft_enchant_set_counted_crafted_keys'] ?? [])));

        if (in_array($key, $countedKeys, true)) {
            $this->discardBatchClonedItemIfOrphaned($craftedItem);
            $progress['craft_enchant_set_counted_crafted_keys'] = $countedKeys;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_craft',
                'phase' => 'crafting',
                'status' => 'failed',
                'failure' => 'This plan key was already counted toward progress. The duplicate crafted item was discarded.',
            ]]];
        }

        $countedKeys[] = $key;
        $progress['craft_enchant_set_counted_crafted_keys'] = array_values(array_unique($countedKeys));
        $progress['craft_enchant_set_craft_index'] = $index;
        $progress['craft_enchant_set_enchant_index'] = $index;
        $progress['craft_enchant_set_phase'] = 'enchanting';
        $progress['craft_enchant_set_surviving_crafted_count'] = ((int) ($progress['craft_enchant_set_surviving_crafted_count'] ?? 0)) + 1;
        $progress['craft_enchant_set_crafted_item_ids'][$key] = $craftedItem->id;
        $progress['craft_enchant_set_destinations'][$key] = $craftResult['destination'] ?? null;
        $progress['craft_enchant_set_current_item'] = $craftedItemSnapshot;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'craft_enchant_set_craft',
            'phase' => 'crafting',
            'status' => 'crafted',
            'crafted_item' => $craftedItemSnapshot,
            'gold_spent' => $goldSpent,
        ]]];
    }

    /**
     * Marks a Craft and Enchant Set plan key as having lost its surviving pipeline item
     * (null stored id, missing Item model, shattered, or discarded partial enchant).
     * Idempotent: a key already present in craft_enchant_set_lost_item_keys is left
     * unchanged so the same loss is never decremented twice. Only decrements the
     * surviving-crafted and completed-work-unit counts, and only by exactly 1, when the
     * key is currently in craft_enchant_set_counted_crafted_keys — a key that never
     * contributed to those counters cannot steal progress from another key.
     */
    private function markCraftEnchantSetItemLost(array $progress, string $key): array
    {
        $lostKeys = array_values(array_unique(array_map('strval', $progress['craft_enchant_set_lost_item_keys'] ?? [])));
        $countedKeys = array_values(array_unique(array_map('strval', $progress['craft_enchant_set_counted_crafted_keys'] ?? [])));

        if (in_array($key, $lostKeys, true)) {
            $progress['craft_enchant_set_lost_item_keys'] = $lostKeys;
            $progress['craft_enchant_set_counted_crafted_keys'] = $countedKeys;

            return $progress;
        }

        $lostKeys[] = $key;
        $progress['craft_enchant_set_lost_item_keys'] = array_values(array_unique($lostKeys));

        if (in_array($key, $countedKeys, true)) {
            $progress['craft_enchant_set_counted_crafted_keys'] = array_values(array_diff($countedKeys, [$key]));
            $progress['craft_enchant_set_surviving_crafted_count'] = max(0, ((int) ($progress['craft_enchant_set_surviving_crafted_count'] ?? 0)) - 1);

        } else {
            $progress['craft_enchant_set_counted_crafted_keys'] = $countedKeys;
        }

        return $progress;
    }

    /**
     * Re-crafts the exact planned item/crafting type for the plan key that lost its
     * surviving pipeline item to a shattered or partial enchant. Before restoring
     * progress, confirms the key is genuinely in craft_enchant_set_lost_item_keys and not
     * already in craft_enchant_set_counted_crafted_keys — otherwise the replacement item
     * is discarded and the batch stops with FAILED rather than double-restoring progress.
     * On success, moves the key from lost to counted, restores the surviving count and
     * work units by exactly 1, and returns to the enchanting phase at the same enchant
     * index so the full requested affix plan is attempted again for this key.
     */
    private function craftEnchantSetReplacementCraftPhaseSingle(
        BatchCrafting $batchCrafting,
        Character $character,
        BatchCraftingDisposition $disposition,
    ): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_enchant_set_queue'] ?? [];
        $keys = $progress['craft_enchant_set_keys'] ?? [];
        $replacementKey = $progress['craft_enchant_set_replacement_key'] ?? null;

        if ($this->shouldCommitKeptOutput($disposition)) {
            $capacityEndReason = $this->retainedOutputCapacityEndReason($batchCrafting, $character, 1);

            if (! is_null($capacityEndReason)) {
                return ['end_reason' => $capacityEndReason];
            }
        }

        if (is_null($replacementKey)) {
            $progress['craft_enchant_set_phase'] = 'enchanting';
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => []];
        }

        $keyIndex = array_search($replacementKey, $keys, true);
        $target = $keyIndex === false ? null : ($queue[$keyIndex] ?? null);

        if (is_null($target)) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_replacement_craft',
                'phase' => 'replacement_crafting',
                'status' => 'failed',
                'failure' => 'Could not find the planned target for the replacement item.',
            ]]];
        }

        $selectedItemId = $progress['craft_enchant_set_selected_item_ids'][$replacementKey] ?? null;
        $item = is_null($selectedItemId)
            ? $this->highestCraftableItemForTarget($character, $target['type'], $target['crafting_type'])
            : $this->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type'])
                ->first(fn ($candidate) => (int) $candidate->id === (int) $selectedItemId);

        if (is_null($item)) {
            $targetLabel = $this->finiteSetTargetLabel($replacementKey);
            $progress['invalid_plan_message'] = 'Batch Crafting stopped because the selected item for ' . $targetLabel . ' is no longer available or craftable.';
            $batchCrafting->update(['progress' => $progress]);

            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, 'actions' => [[
                'action' => 'craft_enchant_set_replacement_craft',
                'phase' => 'replacement_crafting',
                'status' => 'stopped',
                'plan_key' => $replacementKey,
                'target_label' => $targetLabel,
                'failure' => $progress['invalid_plan_message'],
            ]]];
        }

        $goldBeforeCraft = $character->gold;
        $craftResult = $this->craftForResolvedDestination(
            $batchCrafting,
            $character->refresh(),
            $item,
            $target['crafting_type'],
            $disposition,
        );

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_replacement_craft',
                'phase' => 'replacement_crafting',
                'status' => 'failed',
                'failure' => 'Replacement crafting attempt failed. No item was produced.',
            ]]];
        }

        $craftedItem = $craftResult['item'];
        $craftedItemSnapshot = $this->itemDetails($craftedItem, null, false);
        $goldSpent = max(0, $goldBeforeCraft - $character->refresh()->gold);

        $progress = $batchCrafting->fresh()->progress ?? [];
        $lostKeys = array_values(array_unique(array_map('strval', $progress['craft_enchant_set_lost_item_keys'] ?? [])));
        $countedKeys = array_values(array_unique(array_map('strval', $progress['craft_enchant_set_counted_crafted_keys'] ?? [])));

        if (! in_array($replacementKey, $lostKeys, true) || in_array($replacementKey, $countedKeys, true)) {
            $this->discardBatchClonedItemIfOrphaned($craftedItem);

            return ['end_reason' => BatchCraftingEndReason::FAILED, 'actions' => [[
                'action' => 'craft_enchant_set_replacement_craft',
                'phase' => 'replacement_crafting',
                'status' => 'stopped',
                'failure' => 'Replacement progress could not be restored because the plan key was not in a valid lost-item state.',
            ]]];
        }

        $progress['craft_enchant_set_crafted_item_ids'][$replacementKey] = $craftedItem->id;
        $progress['craft_enchant_set_destinations'][$replacementKey] = $craftResult['destination'] ?? null;
        $progress['craft_enchant_set_lost_item_keys'] = array_values(array_diff($lostKeys, [$replacementKey]));
        $countedKeys[] = $replacementKey;
        $progress['craft_enchant_set_counted_crafted_keys'] = array_values(array_unique($countedKeys));
        $progress['craft_enchant_set_surviving_crafted_count'] = ((int) ($progress['craft_enchant_set_surviving_crafted_count'] ?? 0)) + 1;

        $progress['craft_enchant_set_replacement_key'] = null;
        $progress['craft_enchant_set_phase'] = 'enchanting';
        $progress['craft_enchant_set_current_item'] = $craftedItemSnapshot;
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'craft_enchant_set_replacement_craft',
            'phase' => 'replacement_crafting',
            'status' => 'crafted',
            'crafted_item' => $craftedItemSnapshot,
            'gold_spent' => $goldSpent,
        ]]];
    }

    private function craftEnchantSetEnchantPhaseSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['craft_enchant_set_queue'] ?? [];
        $keys = $progress['craft_enchant_set_keys'] ?? [];
        $index = (int) ($progress['craft_enchant_set_enchant_index'] ?? 0);

        if ($index >= count($queue)) {
            return ['end_reason' => BatchCraftingEndReason::CRAFT_ENCHANT_SET_COMPLETE];
        }

        $key = $keys[$index] ?? (string) $index;
        $craftedItemId = $progress['craft_enchant_set_crafted_item_ids'][$key] ?? null;
        $destination = is_array($progress['craft_enchant_set_destinations'][$key] ?? null)
            ? $progress['craft_enchant_set_destinations'][$key]
            : null;

        if (is_null($craftedItemId)) {
            $progress = $this->markCraftEnchantSetItemLost($progress, $key);
            $progress['craft_enchant_set_replacement_key'] = $key;
            $progress['craft_enchant_set_phase'] = 'replacement_crafting';
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'missing_item_replacement_required',
                'failure' => 'The crafted item is missing. A replacement will be crafted before enchanting continues.',
            ]]];
        }

        $craftedItem = Item::find($craftedItemId);

        if (is_null($craftedItem)) {
            $progress['craft_enchant_set_crafted_item_ids'][$key] = null;
            $progress = $this->markCraftEnchantSetItemLost($progress, $key);
            $progress['craft_enchant_set_replacement_key'] = $key;
            $progress['craft_enchant_set_phase'] = 'replacement_crafting';
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'missing_item_replacement_required',
                'failure' => 'The crafted item is missing. A replacement will be crafted before enchanting continues.',
            ]]];
        }

        $beforeSnapshot = $this->itemDetails($craftedItem, null, false);
        $plan = $progress['enchant_plan'][$key] ?? [];
        $prefixId = $plan['prefix_affix_id'] ?? null;
        $suffixId = $plan['suffix_affix_id'] ?? null;
        $prefixAffix = is_null($prefixId) ? null : ItemAffix::find($prefixId);
        $suffixAffix = is_null($suffixId) ? null : ItemAffix::find($suffixId);
        $affixIds = array_values(array_filter([$prefixId, $suffixId], fn ($affixId) => ! is_null($affixId)));

        if (empty($affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::FAILED, 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'stopped',
                'crafted_item' => $beforeSnapshot,
                'failure' => 'No valid prefix or suffix was configured for this Craft and Enchant Set entry.',
            ]]];
        }

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'int_stop_affix_ids' => $affixIds, 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'stopped',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'prefix_affix' => $prefixAffix?->toArray(),
                'suffix_affix' => $suffixAffix?->toArray(),
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $goldBeforeEnchant = $character->gold;
        $enchantAttempt = $this->tryEnchantItem($character->refresh(), $craftedItem, $affixIds);
        $goldSpent = max(0, $goldBeforeEnchant - $character->refresh()->gold);

        $progress = $batchCrafting->fresh()->progress ?? [];

        if (! $enchantAttempt['attempted']) {
            $progress['craft_enchant_set_current_prefix'] = $prefixAffix?->name;
            $progress['craft_enchant_set_current_suffix'] = $suffixAffix?->name;
            $progress['craft_enchant_set_current_prefix_affix'] = $prefixAffix?->toArray();
            $progress['craft_enchant_set_current_suffix_affix'] = $suffixAffix?->toArray();
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'failed',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'prefix_affix' => $prefixAffix?->toArray(),
                'suffix_affix' => $suffixAffix?->toArray(),
                'prefix_applied' => false,
                'suffix_applied' => false,
                'failure' => 'Enchanting service did not apply an enchantment.',
            ]]];
        }

        $afterItem = $enchantAttempt['item'];

        if (is_null($afterItem)) {
            $this->removeDestinationItem($destination);
            $progress['craft_enchant_set_crafted_item_ids'][$key] = null;
            $progress['craft_enchant_set_destinations'][$key] = null;
            $progress = $this->markCraftEnchantSetItemLost($progress, $key);
            $progress['craft_enchant_set_replacement_key'] = $key;
            $progress['craft_enchant_set_phase'] = 'replacement_crafting';
            $progress['craft_enchant_set_current_prefix'] = $prefixAffix?->name;
            $progress['craft_enchant_set_current_suffix'] = $suffixAffix?->name;
            $progress['craft_enchant_set_current_prefix_affix'] = $prefixAffix?->toArray();
            $progress['craft_enchant_set_current_suffix_affix'] = $suffixAffix?->toArray();
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['destroyed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'destroyed',
                'destroyed_item' => $this->removedItemDetails($beforeSnapshot, 'destroyed'),
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'prefix_affix' => $prefixAffix?->toArray(),
                'suffix_affix' => $suffixAffix?->toArray(),
                'prefix_applied' => false,
                'suffix_applied' => false,
                'gold_spent' => $goldSpent,
                'failure' => 'The item shattered while enchanting and was destroyed. A replacement will be crafted.',
            ]]];
        }

        $prefixApplied = ! is_null($prefixId) && $afterItem->item_prefix_id === $prefixId;
        $suffixApplied = ! is_null($suffixId) && $afterItem->item_suffix_id === $suffixId;
        $enchantedSnapshot = $this->itemDetails($afterItem, null, false);
        $requestedAffixesSatisfied = $this->requestedAffixesSatisfied($afterItem, $affixIds);

        $progress['craft_enchant_set_current_prefix'] = $prefixAffix?->name;
        $progress['craft_enchant_set_current_suffix'] = $suffixAffix?->name;
        $progress['craft_enchant_set_current_prefix_affix'] = $prefixAffix?->toArray();
        $progress['craft_enchant_set_current_suffix_affix'] = $suffixAffix?->toArray();

        if (! $requestedAffixesSatisfied) {
            $this->removeDestinationItem($destination);
            $this->discardPartialBatchItem($afterItem);

            $progress['craft_enchant_set_crafted_item_ids'][$key] = null;
            $progress['craft_enchant_set_destinations'][$key] = null;
            $progress = $this->markCraftEnchantSetItemLost($progress, $key);
            $progress['craft_enchant_set_replacement_key'] = $key;
            $progress['craft_enchant_set_phase'] = 'replacement_crafting';
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft_enchant_set_enchant',
                'phase' => 'enchanting',
                'status' => 'partial_enchant_discarded',
                'crafted_item' => $beforeSnapshot,
                'prefix_affix_name' => $prefixAffix?->name,
                'suffix_affix_name' => $suffixAffix?->name,
                'prefix_affix' => $prefixAffix?->toArray(),
                'suffix_affix' => $suffixAffix?->toArray(),
                'prefix_applied' => $prefixApplied,
                'suffix_applied' => $suffixApplied,
                'gold_spent' => $goldSpent,
                'failure' => 'The item did not receive every requested enchantment and was discarded. A replacement will be crafted.',
            ]]];
        }

        if ($prefixApplied) {
            $progress['craft_enchant_set_prefix_applied_count'] = ((int) ($progress['craft_enchant_set_prefix_applied_count'] ?? 0)) + 1;
        }

        if ($suffixApplied) {
            $progress['craft_enchant_set_suffix_applied_count'] = ((int) ($progress['craft_enchant_set_suffix_applied_count'] ?? 0)) + 1;
        }

        $this->replaceDestinationItem($destination, $afterItem);
        $progress['craft_enchant_set_crafted_item_ids'][$key] = $afterItem->id;
        $progress['craft_enchant_set_current_item'] = $enchantedSnapshot;
        $progress['craft_enchant_set_enchant_index'] = $index + 1;
        $progress['craft_enchant_set_craft_index'] = $index + 1;
        $progress['craft_enchant_set_phase'] = 'crafting';
        $progress['craft_enchant_set_completed_work_units'] = ((int) ($progress['craft_enchant_set_completed_work_units'] ?? 0)) + 1;
        $progress['craft_enchant_set_completed_final_count'] = ((int) ($progress['craft_enchant_set_completed_final_count'] ?? 0)) + 1;
        $batchCrafting->update(['progress' => $progress]);

        $dispositionResult = $this->dispositionForCraftResult(
            $batchCrafting,
            $character->refresh(),
            $afterItem,
            $disposition,
            $destination,
        );
        $result = ['counts' => $this->mergeCounts(['enchanted_count' => 1], $dispositionResult['counts']), 'actions' => [[
            'action' => 'craft_enchant_set_enchant',
            'phase' => 'enchanting',
            'status' => $prefixApplied && $suffixApplied ? 'double_enchanted' : 'enchanted',
            'crafted_item' => $beforeSnapshot,
            'enchanted_item' => $enchantedSnapshot,
            'prefix_affix_name' => $prefixAffix?->name,
            'suffix_affix_name' => $suffixAffix?->name,
            'prefix_affix' => $prefixAffix?->toArray(),
            'suffix_affix' => $suffixAffix?->toArray(),
            'prefix_applied' => $prefixApplied,
            'suffix_applied' => $suffixApplied,
            'gold_spent' => $goldSpent,
        ] + $dispositionResult['details']], 'kept_output_committed' => $this->shouldCommitKeptOutput($disposition)];

        return $result;
    }

    private function processEventCraft(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventCraftUnavailableReason($batchCrafting, $character)];
        }

        return $this->processRepeatedActions(BatchCraftingService::EVENT_ITEMS_PER_SET_TICK, function () use ($batchCrafting, $character) {
            return $this->processEventCraftSingle($batchCrafting->refresh(), $character->refresh());
        });
    }

    private function processEventCraftSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventCraftUnavailableReason($batchCrafting, $character)];
        }

        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['event_craft_queue'] ?? [];
        $index = (int) ($progress['event_craft_index'] ?? 0);

        if (empty($queue)) {
            return ['end_reason' => BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS];
        }

        if ($index >= count($queue)) {
            $index = 0;
        }

        $target = $queue[$index];
        $progress['event_craft_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);
        $item = $this->eventCraftableItem($character, $target);

        if (is_null($item)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'event_craft',
                'status' => 'skipped',
                'failure' => 'No event craftable item found for type: '.$target['type'],
            ]]];
        }

        $skillBefore = $this->craftingSkillSnapshot($character, $target['crafting_type']);
        $crafted = $this->craftingService->craftForBatch($character->refresh(), $item, $target['crafting_type']);
        $craftingXpGained = $this->skillExperienceGained($skillBefore, $this->craftingSkillSnapshot($character->refresh(), $target['crafting_type']));

        if (! $crafted['success'] || is_null($crafted['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'event_craft',
                'failure' => 'Crafting attempt failed. No event item was produced.',
            ]]];
        }

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character->refresh(), $crafted['item']);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'event_craft',
            'crafted_item' => $this->itemDetails($item),
            'event_goal_id' => $goal->id,
            'crafting_xp_gained' => $craftingXpGained,
        ]]];
    }

    private function processEventEnchant(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventEnchantUnavailableReason($batchCrafting, $character)];
        }

        $progress = $batchCrafting->progress ?? [];
        $eventSlot = $this->nextEventInventorySlot($character, $goal);

        if (! is_null($eventSlot) && ($progress['event_enchant_phase'] ?? 'enchant_event_inventory') === 'enchant_event_inventory') {
            return $this->processUntilDepletedOrEndReason(BatchCraftingService::EVENT_ITEMS_PER_SET_TICK, function () use ($batchCrafting, $character) {
                return $this->processEventEnchantSingle($batchCrafting->refresh(), $character->refresh());
            });
        }

        if (($progress['event_enchant_phase'] ?? 'enchant_event_inventory') !== 'enchant_fallback_set') {
            $craftResult = $this->processEventFallbackCraftSet($batchCrafting, $character);

            if (isset($craftResult['end_reason'])) {
                return $craftResult;
            }

            $enchantResult = $this->processEventFallbackEnchantSet($batchCrafting->refresh(), $character->refresh());

            return $this->mergeTickResults($craftResult, $enchantResult);
        }

        return $this->processEventFallbackEnchantSet($batchCrafting, $character);
    }

    private function processEventEnchantSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventEnchantUnavailableReason($batchCrafting, $character)];
        }

        $slot = $this->nextEventInventorySlot($character, $goal);

        if (is_null($slot)) {
            return ['depleted' => true];
        }

        $affixIds = $this->eventBatchEnchantingAffixSelector->affixIdsForEventEnchant($character, $slot->item);

        if (empty($affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::EVENT_NO_AFFIXES];
        }

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'int_stop_affix_ids' => $affixIds, 'actions' => [[
                'action' => 'event_enchant',
                'status' => 'stopped',
                'crafted_item' => $this->itemDetails($slot->item, $slot->id),
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $slot->item_id);

        if ($character->gold < $cost) {
            return ['end_reason' => BatchCraftingEndReason::NO_CURRENCY];
        }

        return $this->finalizeEventEnchantAttempt($character, $slot, $affixIds, $cost, $goal, 'event_enchant');
    }

    /**
     * Applies the selected event affixes to $slot with the event handover suppressed
     * (enchant_for_event => false), then only hands the item to the event goal — via
     * the existing HandleUpdatingEnchantingGlobalEventGoal handler, which remains the
     * only place event-goal progress is mutated — once every exact selected affix is
     * confirmed to be on the surviving item. A shattered item is reported destroyed, a
     * surviving item missing a selected affix is reported failed and left available for
     * a later attempt, and a fully enchanted item the handler does not accept is also
     * reported failed rather than counted as enchanted.
     */
    private function finalizeEventEnchantAttempt(
        Character $character,
        GlobalEventCraftingInventorySlot $slot,
        array $affixIds,
        int $cost,
        GlobalEventGoal $goal,
        string $actionName
    ): array {
        $preEnchantSnapshot = $this->itemDetails($slot->item, $slot->id);
        $slotId = $slot->id;
        $skillBefore = $this->skillSnapshot($character, SkillTypeValue::ENCHANTING->value, true);

        $this->enchantingService->enchant($character, [
            'affix_ids' => $affixIds,
            'enchant_for_event' => false,
        ], $slot, $cost);

        $character = $character->refresh();
        $remainingSlot = GlobalEventCraftingInventorySlot::with('item')->find($slotId);

        if (is_null($remainingSlot)) {
            return ['pause_tick' => true, 'counts' => ['destroyed_count' => 1], 'actions' => [[
                'action' => $actionName,
                'status' => 'destroyed',
                'destroyed_item' => $this->removedItemDetails($preEnchantSnapshot, 'destroyed'),
                'failure' => 'The event item shattered while enchanting and was destroyed.',
            ]]];
        }

        if (! $this->requestedAffixesSatisfied($remainingSlot->item, $affixIds)) {
            return ['pause_tick' => true, 'counts' => ['failed_count' => 1], 'actions' => [[
                'action' => $actionName,
                'status' => 'failed',
                'crafted_item' => $this->itemDetails($remainingSlot->item, $remainingSlot->id),
                'failure' => 'The event item did not receive every selected enchantment. It will be attempted again.',
            ]]];
        }

        $enchantedSnapshot = $this->itemDetails($remainingSlot->item, $remainingSlot->id);
        $enchantingXpGained = $this->skillExperienceGained($skillBefore, $this->skillSnapshot($character->refresh(), SkillTypeValue::ENCHANTING->value, true));

        $this->handleUpdatingEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character->refresh(), $remainingSlot);

        if (! is_null(GlobalEventCraftingInventorySlot::find($slotId))) {
            return ['pause_tick' => true, 'counts' => ['failed_count' => 1], 'actions' => [[
                'action' => $actionName,
                'status' => 'failed',
                'crafted_item' => $enchantedSnapshot,
                'failure' => 'The fully enchanted event item could not be handed over. It will be attempted again.',
            ]]];
        }

        $enchantingXpGained = $enchantingXpGained > 0 ? $enchantingXpGained : $this->acceptedEventEnchantExperience($character->refresh(), $remainingSlot);

        return ['counts' => ['enchanted_count' => 1], 'actions' => [[
            'action' => $actionName,
            'enchanted_item' => $enchantedSnapshot,
            'event_goal_id' => $goal->id,
            'affix_count' => count($affixIds),
            'enchanting_xp_gained' => $enchantingXpGained,
        ]]];
    }

    private function processEventFallbackCraftSet(BatchCrafting $batchCrafting, Character $character): array
    {
        if (! $this->batchCraftingSetService->canAccept($character, BatchCraftingService::EVENT_ITEMS_PER_SET_TICK)) {
            return ['end_reason' => BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL];
        }

        $progress = $batchCrafting->progress ?? [];
        $progress['event_enchant_phase'] = 'craft_fallback_set';
        $progress['event_fallback_phase'] = 'craft_fallback_set';
        $progress['event_fallback_event_slot_ids'] = [];
        $batchCrafting->update(['progress' => $progress]);

        $result = $this->processRepeatedActions(BatchCraftingService::EVENT_ITEMS_PER_SET_TICK, function () use ($batchCrafting, $character) {
            return $this->processEventFallbackCraftSingle($batchCrafting->refresh(), $character->refresh());
        });

        $progress = $batchCrafting->refresh()->progress ?? [];
        $progress['event_enchant_phase'] = 'enchant_fallback_set';
        $progress['event_fallback_phase'] = 'enchant_fallback_set';
        $progress['event_fallback_crafted_this_tick'] = $result['counts']['crafted_count'] ?? 0;
        $batchCrafting->update(['progress' => $progress]);

        return $result;
    }

    private function processEventFallbackCraftSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $queue = $progress['event_craft_queue'] ?? [
            ['type' => 'weapon', 'crafting_type' => 'weapon'],
            ['type' => 'armour', 'crafting_type' => 'armour'],
            ['type' => 'ring', 'crafting_type' => 'ring'],
            ['type' => 'spell_damage', 'crafting_type' => 'spell'],
            ['type' => 'spell_healing', 'crafting_type' => 'spell'],
        ];
        $index = (int) ($progress['event_craft_index'] ?? 0);

        if ($index >= count($queue)) {
            $index = 0;
        }

        $target = $queue[$index];
        $item = $this->eventCraftableItem($character, $target);
        $progress['event_craft_index'] = $index + 1;
        $batchCrafting->update(['progress' => $progress]);

        if (is_null($item)) {
            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'event_fallback_craft',
                'status' => 'skipped',
                'failure' => 'No fallback craftable item found for type: '.$target['type'],
            ]]];
        }

        $skillBefore = $this->craftingSkillSnapshot($character, $target['crafting_type']);
        $crafted = $this->craftingService->craftForBatch($character->refresh(), $item, $target['crafting_type']);
        $craftingXpGained = $this->skillExperienceGained($skillBefore, $this->craftingSkillSnapshot($character->refresh(), $target['crafting_type']));

        if (! $crafted['success'] || is_null($crafted['item'])) {
            return ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'event_fallback_craft',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];
        }

        $eventSlot = $this->createEventFallbackSlot($character->refresh(), $crafted['item']);
        $progress = $batchCrafting->refresh()->progress ?? [];
        $progress['event_fallback_event_slot_ids'] = array_values(array_filter([
            ...($progress['event_fallback_event_slot_ids'] ?? []),
            $eventSlot->id,
        ]));
        $batchCrafting->update(['progress' => $progress]);

        return ['counts' => ['crafted_count' => 1], 'actions' => [[
            'action' => 'event_fallback_craft',
            'crafted_item' => $this->itemDetails($crafted['item'], $eventSlot->id),
            'crafting_xp_gained' => $craftingXpGained,
        ]]];
    }

    private function createEventFallbackSlot(Character $character, Item $item): GlobalEventCraftingInventorySlot
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);
        $inventory = GlobalEventCraftingInventory::firstOrCreate([
            'global_event_goal_id' => $goal?->id,
            'character_id' => $character->id,
        ]);

        return GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $item->id,
        ]);
    }

    private function processEventFallbackEnchantSet(BatchCrafting $batchCrafting, Character $character): array
    {
        $result = $this->processUntilDepletedOrEndReason(BatchCraftingService::EVENT_ITEMS_PER_SET_TICK, function () use ($batchCrafting, $character) {
            return $this->processEventFallbackEnchantSingle($batchCrafting->refresh(), $character->refresh());
        });

        $progress = $batchCrafting->refresh()->progress ?? [];
        $slotIds = array_values($progress['event_fallback_event_slot_ids'] ?? []);
        $nextPhase = empty($slotIds) ? 'craft_fallback_set' : 'enchant_fallback_set';
        $progress['event_enchant_phase'] = $nextPhase;
        $progress['event_fallback_phase'] = $nextPhase;
        $progress['event_fallback_enchanted_this_tick'] = $result['counts']['enchanted_count'] ?? 0;
        $batchCrafting->update(['progress' => $progress]);

        return $result;
    }

    private function processEventFallbackEnchantSingle(BatchCrafting $batchCrafting, Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            return ['end_reason' => $this->eventEnchantUnavailableReason($batchCrafting, $character)];
        }

        $progress = $batchCrafting->progress ?? [];
        $slotIds = array_values($progress['event_fallback_event_slot_ids'] ?? []);
        $slotId = $slotIds[0] ?? null;

        if (is_null($slotId)) {
            return ['depleted' => true];
        }

        $slot = GlobalEventCraftingInventorySlot::find($slotId);

        if (is_null($slot)) {
            array_shift($slotIds);
            $progress['event_fallback_event_slot_ids'] = $slotIds;
            $batchCrafting->update(['progress' => $progress]);

            return ['counts' => ['skipped_count' => 1], 'actions' => [[
                'action' => 'event_fallback_enchant',
                'status' => 'skipped',
                'failure' => 'Fallback crafted event item was removed before enchanting.',
            ]]];
        }

        $affixIds = $this->eventBatchEnchantingAffixSelector->affixIdsForEventEnchant($character, $slot->item);

        if (empty($affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::EVENT_NO_AFFIXES];
        }

        if ($this->intBlockedForEnchant($character, $affixIds)) {
            return ['end_reason' => BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, 'int_stop_affix_ids' => $affixIds, 'actions' => [[
                'action' => 'event_fallback_enchant',
                'status' => 'stopped',
                'crafted_item' => $this->itemDetails($slot->item, $slot->id),
                'failure' => 'Your Intelligence is too low for the selected enchantment.',
            ]]];
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $slot->item_id);

        if ($character->gold < $cost) {
            return ['end_reason' => BatchCraftingEndReason::NO_CURRENCY];
        }

        $result = $this->finalizeEventEnchantAttempt($character, $slot, $affixIds, $cost, $goal, 'event_fallback_enchant');

        if (is_null(GlobalEventCraftingInventorySlot::find($slotId))) {
            array_shift($slotIds);
        }

        $progress = $batchCrafting->refresh()->progress ?? [];
        $progress['event_fallback_event_slot_ids'] = $slotIds;
        $batchCrafting->update(['progress' => $progress]);

        return $result;
    }

    private function processCraftSpecificItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $craftedSoFar = (int) ($progress['craft_specific_count'] ?? 0);

        if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        return $this->processCraftSpecificItemSingle($batchCrafting, $character, $disposition, $progress);
    }

    private function processCraftSpecificItemSingle(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $craftAmount = (int) ($progress['craft_amount'] ?? 0);
        $craftedSoFar = (int) ($progress['craft_specific_count'] ?? 0);

        if ($craftAmount > 0 && $craftedSoFar >= $craftAmount) {
            return ['end_reason' => BatchCraftingEndReason::AMOUNT_REACHED];
        }

        $capacityEndReason = $this->retainedOutputCapacityEndReason($batchCrafting, $character, 1);

        if (! is_null($capacityEndReason)) {
            return ['end_reason' => $capacityEndReason];
        }

        $craftingType = $progress['specific_crafting_type'] ?? null;
        $itemId = (int) ($progress['specific_item_id'] ?? 0);

        if (is_null($craftingType) || $itemId <= 0) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $craftableItems = $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);

        $craftableItem = $craftableItems->first(fn ($item) => (int) $item->id === $itemId);
        $item = is_null($craftableItem) ? null : Item::find($craftableItem->id);

        if (is_null($item)) {
            return ['end_reason' => BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT];
        }

        $result = $this->craftItem($batchCrafting, $character, $disposition, $item, $craftingType, $this->shouldCommitKeptOutput($disposition));

        if (isset($result['counts']['crafted_count']) && $result['counts']['crafted_count'] > 0) {
            $progress = $batchCrafting->fresh()->progress ?? [];
            $progress['craft_specific_count'] = $craftedSoFar + 1;
            $batchCrafting->update(['progress' => $progress]);
        }

        return $result;
    }

    private function craftItem(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, Item $item, string $craftingType, bool $suppressSuccessServerMessage = false): array
    {
        $craftResult = $this->craftForResolvedDestination(
            $batchCrafting,
            $character->refresh(),
            $item,
            $craftingType,
            $disposition,
        );

        if (! $craftResult['success'] || is_null($craftResult['item'])) {
            $result = ['counts' => ['failed_count' => 1], 'actions' => [[
                'action' => 'craft',
                'status' => 'failed',
                'failure' => 'Crafting attempt failed. No item was produced.',
            ]]];

            if (($craftResult['reason'] ?? null) === 'destination_failed') {
                $result['end_reason'] = BatchCraftingEndReason::FAILED;
            }

            return $result;
        }

        $craftedItem = $craftResult['item'];
        $action = ['action' => 'craft', 'crafted_item' => $this->itemDetails($craftedItem)];
        $counts = ['crafted_count' => 1];
        $dispositionResult = $this->dispositionForCraftResult(
            $batchCrafting,
            $character->refresh(),
            $craftedItem,
            $disposition,
            $craftResult['destination'] ?? null,
        );
        $counts = $this->mergeCounts($counts, $dispositionResult['counts']);
        $action = array_merge($action, $dispositionResult['details']);

        $result = [
            'counts' => $counts,
            'actions' => [$action],
            'kept_output_committed' => $this->shouldCommitKeptOutput($disposition),
        ];

        if (isset($dispositionResult['end_reason'])) {
            $result['end_reason'] = $dispositionResult['end_reason'];
        }

        return $result;
    }

    private function craftExperienceTargets(?string $selectedSkill): array
    {
        return match ($selectedSkill) {
            'weapon' => array_map(fn (string $weaponType) => ['type' => $weaponType, 'crafting_type' => $weaponType], ItemType::validWeapons()),
            'armour' => array_map(fn (string $armourType) => ['type' => $armourType, 'crafting_type' => 'armour'], ArmourType::allTypes()),
            'ring' => [
                ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
            ],
            'spell' => [
                ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'],
                ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'],
            ],
            default => array_merge(
                array_map(fn (string $weaponType) => ['type' => $weaponType, 'crafting_type' => $weaponType], ItemType::validWeapons()),
                array_map(fn (string $armourType) => ['type' => $armourType, 'crafting_type' => 'armour'], ArmourType::allTypes()),
                [
                    ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
                    ['type' => ItemType::RING->value, 'crafting_type' => 'ring'],
                    ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'],
                    ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'],
                ],
            ),
        };
    }

    private function allCraftingSkillsMaxed(Character $character): bool
    {
        $craftingSkills = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->whereIn('name', [
                'Weapon Crafting',
                'Armour Crafting',
                'Ring Crafting',
                'Spell Crafting',
            ]))
            ->with('baseSkill')
            ->get();

        return $craftingSkills->isNotEmpty() && $craftingSkills->every(fn ($skill) => $skill->level >= $skill->max_level);
    }

    private function isEnchantingSkillMaxed(Character $character): bool
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->with('baseSkill')
            ->first();

        return ! is_null($skill) && $skill->level >= $skill->max_level;
    }

    private function nonMaxedCraftExperienceTargets(Character $character): array
    {
        $craftingSkills = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->whereIn('name', [
                'Weapon Crafting',
                'Armour Crafting',
                'Ring Crafting',
                'Spell Crafting',
            ]))
            ->with('baseSkill')
            ->get();

        $maxedSkillNames = $craftingSkills
            ->filter(fn ($skill) => $skill->level >= $skill->max_level)
            ->map(fn ($skill) => $skill->baseSkill->name ?? $skill->name)
            ->all();

        $targets = [];

        if (! in_array('Weapon Crafting', $maxedSkillNames, true)) {
            foreach (ItemType::validWeapons() as $weaponType) {
                $targets[] = ['type' => $weaponType, 'crafting_type' => $weaponType];
            }
        }

        if (! in_array('Armour Crafting', $maxedSkillNames, true)) {
            foreach (ArmourType::allTypes() as $armourType) {
                $targets[] = ['type' => $armourType, 'crafting_type' => 'armour'];
            }
        }

        if (! in_array('Ring Crafting', $maxedSkillNames, true)) {
            $targets[] = ['type' => ItemType::RING->value, 'crafting_type' => 'ring'];
            $targets[] = ['type' => ItemType::RING->value, 'crafting_type' => 'ring'];
        }

        if (! in_array('Spell Crafting', $maxedSkillNames, true)) {
            $targets[] = ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'];
            $targets[] = ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'];
        }

        return $targets;
    }

    private function specificCraftableItems(Character $character, string $craftingType)
    {
        return $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);
    }

    private function eventCraftableItem(Character $character, array $target): ?Item
    {
        if ($target['type'] === 'weapon') {
            foreach (ItemType::validWeapons() as $weaponType) {
                $item = $this->craftableItemForTarget($character, $weaponType, $weaponType);

                if (! is_null($item)) {
                    return $item;
                }
            }

            return null;
        }

        if ($target['type'] === 'armour') {
            foreach (ArmourType::allTypes() as $armourType) {
                $item = $this->craftableItemForTarget($character, $armourType, 'armour');

                if (! is_null($item)) {
                    return $item;
                }
            }

            return null;
        }

        return $this->craftableItemForTarget($character, $target['type'], $target['crafting_type']);
    }

    private function craftableItemForTarget(Character $character, string $type, string $craftingType): ?Item
    {
        try {
            $craftableItem = $this->specificCraftableItems($character, $craftingType)
                ->filter(fn ($item) => $item->type === $type)
                ->sortBy([
                    ['cost', 'asc'],
                    ['skill_level_required', 'asc'],
                    ['id', 'asc'],
                ])
                ->first();
        } catch (\Throwable) {
            return null;
        }

        return is_null($craftableItem) ? null : Item::find($craftableItem->id);
    }

    private function xpEligibleCraftableItem(Character $character, string $craftingType, string $type): ?Item
    {
        try {
            $candidates = $this->specificCraftableItems($character, $craftingType)
                ->filter(fn ($craftableItem) => $craftableItem->type === $type);
        } catch (\Throwable) {
            return null;
        }

        $skillLevel = $this->craftingSkillLevelForType($character, $craftingType);

        return $this->xpEligibleItemFromCandidates($candidates, $skillLevel);
    }

    private function craftingSkillLevelForType(Character $character, string $craftingType): ?int
    {
        $skillName = in_array($craftingType, ItemType::validWeapons(), true)
            ? 'Weapon Crafting'
            : ucfirst($craftingType) . ' Crafting';

        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('name', $skillName))
            ->first();

        return $skill?->level;
    }

    private function xpEligibleItemFromCandidates(Collection $candidates, ?int $skillLevel): ?Item
    {
        if (is_null($skillLevel) || $candidates->isEmpty()) {
            return null;
        }

        return Item::whereIn('id', $candidates->pluck('id'))
            ->where('skill_level_trivial', '>=', $skillLevel)
            ->orderByDesc('skill_level_required')
            ->first();
    }

    private function highestCraftableItemForTarget(Character $character, string $type, string $craftingType): ?Item
    {
        return $this->craftableItemCandidatesForTarget($character, $type, $craftingType)->first();
    }

    private function finiteSetTargetLabel(string $key): string
    {
        $target = collect($this->craftSetPlannerTargets())->firstWhere('key', $key);

        return $target['label'] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    private function mergeTickResults(array $firstResult, array $secondResult): array
    {
        $result = [
            'counts' => $this->mergeCounts($firstResult['counts'] ?? [], $secondResult['counts'] ?? []),
            'actions' => array_merge($firstResult['actions'] ?? [], $secondResult['actions'] ?? []),
        ];

        if (isset($secondResult['end_reason'])) {
            $result['end_reason'] = $secondResult['end_reason'];
        }

        if (isset($firstResult['end_reason'])) {
            $result['end_reason'] = $firstResult['end_reason'];
        }

        if (isset($secondResult['int_stop_affix_ids'])) {
            $result['int_stop_affix_ids'] = $secondResult['int_stop_affix_ids'];
        }

        if (isset($firstResult['int_stop_affix_ids'])) {
            $result['int_stop_affix_ids'] = $firstResult['int_stop_affix_ids'];
        }

        return $result;
    }

    private function nextEventInventorySlot(Character $character, GlobalEventGoal $goal): ?GlobalEventCraftingInventorySlot
    {
        $inventory = GlobalEventCraftingInventory::where('global_event_goal_id', $goal->id)
            ->where('character_id', $character->id)
            ->first();

        if (is_null($inventory)) {
            return null;
        }

        return $inventory->craftingSlots()->with('item')->orderBy('id')->first();
    }

    private function eventCraftUnavailableReason(BatchCrafting $batchCrafting, Character $character): BatchCraftingEndReason
    {
        $progress = $batchCrafting->progress ?? [];
        $goal = GlobalEventGoal::find($progress['event_goal_id'] ?? null);

        if (! is_null($goal) && ! is_null($goal->max_crafts) && $goal->total_crafts >= $goal->max_crafts) {
            return BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
        }

        $event = Event::find($progress['event_id'] ?? null);

        if (is_null($event) || ! $this->globalEventGoalEligibilityService->isEventRunning($event)) {
            return BatchCraftingEndReason::EVENT_NOT_RUNNING;
        }

        if ($event->current_event_goal_step !== GlobalEventSteps::CRAFT) {
            return BatchCraftingEndReason::EVENT_STEP_CHANGED;
        }

        if (! $this->globalEventGoalEligibilityService->isOnEventMap($character, $event)) {
            return BatchCraftingEndReason::EVENT_WRONG_MAP;
        }

        return BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS;
    }

    private function eventEnchantUnavailableReason(BatchCrafting $batchCrafting, Character $character): BatchCraftingEndReason
    {
        $progress = $batchCrafting->progress ?? [];
        $goal = GlobalEventGoal::find($progress['event_goal_id'] ?? null);

        if (! is_null($goal) && ! is_null($goal->max_enchants) && $goal->total_enchants >= $goal->max_enchants) {
            return BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
        }

        $event = Event::find($progress['event_id'] ?? null);

        if (is_null($event) || ! $this->globalEventGoalEligibilityService->isEventRunning($event)) {
            return BatchCraftingEndReason::EVENT_NOT_RUNNING;
        }

        if ($event->current_event_goal_step !== GlobalEventSteps::ENCHANT) {
            return BatchCraftingEndReason::EVENT_STEP_CHANGED;
        }

        if (! $this->globalEventGoalEligibilityService->isOnEventMap($character, $event)) {
            return BatchCraftingEndReason::EVENT_WRONG_MAP;
        }

        return BatchCraftingEndReason::EVENT_NO_EVENT_ITEMS_TO_ENCHANT;
    }

    private function processRepeatedActions(int $attempts, callable $callback): array
    {
        $counts = [];
        $actions = [];

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $result = $callback();

            if (isset($result['end_reason'])) {
                $counts = $this->mergeCounts($counts, $result['counts'] ?? []);
                $actions = array_merge($actions, $result['actions'] ?? []);

                if (! empty($actions)) {
                    $endResult = [
                        'end_reason' => $result['end_reason'],
                        'counts' => $counts,
                        'actions' => $actions,
                    ];

                    if (isset($result['int_stop_affix_ids'])) {
                        $endResult['int_stop_affix_ids'] = $result['int_stop_affix_ids'];
                    }

                    return $endResult;
                }

                return $result;
            }

            $counts = $this->mergeCounts($counts, $result['counts'] ?? []);
            $actions = array_merge($actions, $result['actions'] ?? []);
        }

        return ['counts' => $counts, 'actions' => $actions];
    }

    // A callback returning ['depleted' => true] stops the loop without an end_reason, so running out of source items mid-tick ends the tick normally instead of ending the whole batch job.
    private function processUntilDepletedOrEndReason(int $attempts, callable $callback): array
    {
        $counts = [];
        $actions = [];

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $result = $callback();

            $counts = $this->mergeCounts($counts, $result['counts'] ?? []);
            $actions = array_merge($actions, $result['actions'] ?? []);

            if (isset($result['end_reason'])) {
                $endResult = [
                    'end_reason' => $result['end_reason'],
                    'counts' => $counts,
                    'actions' => $actions,
                ];

                if (isset($result['int_stop_affix_ids'])) {
                    $endResult['int_stop_affix_ids'] = $result['int_stop_affix_ids'];
                }

                return $endResult;
            }

            if (($result['depleted'] ?? false) === true || ($result['pause_tick'] ?? false) === true) {
                break;
            }
        }

        return ['counts' => $counts, 'actions' => $actions];
    }

    private function shouldCommitKeptOutput(BatchCraftingDisposition $disposition): bool
    {
        return in_array($disposition, [
            BatchCraftingDisposition::KEEP,
            BatchCraftingDisposition::KEEP_HIGHEST,
            BatchCraftingDisposition::KEEP_BEST_SELL_REST,
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST,
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
        ], true);
    }

    /**
     * Resolves whether the retained output for the next kept item has enough capacity
     * at its destination before crafting is attempted. Craft Amount, Craft Set, Craft
     * and Enchant Amount and Craft and Enchant Set are selectable finite KEEP modes:
     * their destination (Inventory, a specific Inventory Set, or the Crafted Items Set)
     * comes from progress.output_destination. Every other retained-output workflow
     * (experience mode, Trinketry, and the legacy Keep Highest/Keep Best dispositions)
     * always uses the Crafted Items Set and is never destination-selectable.
     */
    private function retainedOutputCapacityEndReason(BatchCrafting $batchCrafting, Character $character, int $requiredSlots = 1): ?BatchCraftingEndReason
    {
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);

        if (! $this->shouldCommitKeptOutput($disposition)) {
            return null;
        }

        $progress = $batchCrafting->progress ?? [];
        $craftMode = $progress['craft_mode'] ?? 'experience';
        $isSelectableFiniteKeepMode = $disposition === BatchCraftingDisposition::KEEP
            && in_array($batchCrafting->batch_type, [BatchCraftingType::CRAFT->value, BatchCraftingType::CRAFT_AND_ENCHANT->value], true)
            && in_array($craftMode, ['specific_item', 'craft_set', 'craft_enchant_set'], true);

        if (! $isSelectableFiniteKeepMode) {
            if (! $this->batchCraftingSetService->canAccept($character, $requiredSlots)) {
                return BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL;
            }

            return null;
        }

        $destination = $progress['output_destination'] ?? 'crafted_items_set';

        if ($destination === 'inventory') {
            $character = $character->refresh();
            $freeSlots = $character->inventory_max - $character->getInventoryCount();

            if ($freeSlots < $requiredSlots) {
                return BatchCraftingEndReason::NO_INVENTORY_SPACE;
            }

            return null;
        }

        if ($destination === 'inventory_set') {
            $outputSet = InventorySet::where('id', (int) ($progress['output_set_id'] ?? 0))
                ->where('character_id', $character->id)
                ->first();

            if (is_null($outputSet) || $outputSet->isBatchCraftingSet() || $outputSet->is_equipped) {
                return BatchCraftingEndReason::CRAFT_ENCHANT_SET_TARGET_SET_CHANGED;
            }

            if ($outputSet->remainingSlots() < $requiredSlots) {
                return BatchCraftingEndReason::CRAFT_SET_FULL;
            }

            return null;
        }

        if (! $this->batchCraftingSetService->canAccept($character, $requiredSlots)) {
            return BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL;
        }

        return null;
    }

    /**
     * Bounds a single Craft / Craft and Enchant experience-mode tick to at most
     * ITEMS_PER_EXPERIENCE_TICK actions, so a full ITEMS_PER_FULL_SET cycle is
     * spread across sequential 6/6/6/5 chunks instead of running all at once.
     */
    private function experienceCycleChunkSize(array $progress): int
    {
        $cycleActionsSoFar = (int) ($progress['experience_cycle_actions'] ?? 0);

        return max(0, min(BatchCraftingService::ITEMS_PER_EXPERIENCE_TICK, BatchCraftingService::ITEMS_PER_FULL_SET - $cycleActionsSoFar));
    }

    /**
     * Advances experience_cycle_actions by however many actions this chunk actually
     * completed, resetting it to 0 once a full ITEMS_PER_FULL_SET cycle is reached.
     * Skipped when the tick ended the batch, since the progress row no longer matters.
     */
    private function recordExperienceCycleProgress(BatchCrafting $batchCrafting, array $progress, array $result): void
    {
        if (isset($result['end_reason'])) {
            return;
        }

        $cycleActionsSoFar = (int) ($progress['experience_cycle_actions'] ?? 0);
        $newCycleActions = $cycleActionsSoFar + count($result['actions'] ?? []);

        if ($newCycleActions >= BatchCraftingService::ITEMS_PER_FULL_SET) {
            $newCycleActions = 0;
        }

        $updatedProgress = $batchCrafting->fresh()->progress ?? [];
        $updatedProgress['experience_cycle_actions'] = $newCycleActions;
        $batchCrafting->update(['progress' => $updatedProgress]);
    }

    private function retainedCraftedItemsSetSlotsForExperience(BatchCraftingDisposition $disposition, array $progress): int
    {
        if ($disposition === BatchCraftingDisposition::KEEP) {
            $cycleActionsSoFar = (int) ($progress['experience_cycle_actions'] ?? 0);
            $cycleActionsSoFar = max(0, min(BatchCraftingService::ITEMS_PER_FULL_SET, $cycleActionsSoFar));

            return BatchCraftingService::ITEMS_PER_FULL_SET - $cycleActionsSoFar;
        }

        return count(collect($this->craftExperienceTargets(null))->unique('type'));
    }

    /**
     * Commits a kept item (already crafted, disposition KEEP) to its resolved output
     * destination: the Crafted Items Set (default/backward-compatible), normal
     * Inventory, or a specific empty inventory set selected at start. Never places the
     * same item in more than one destination. Re-checks actual current capacity so a
     * destination filled by something else between ticks stops cleanly instead of
     * losing the item.
     */
    /**
     * Exact requested-affix atomicity rule shared by every build-new Craft and Enchant
     * path: every requested prefix/suffix id must exist exactly on the item. An empty
     * requested list is never success for a path that requires enchanting, and "at
     * least one affix applied" is never treated as success.
     */
    private function requestedAffixesSatisfied(Item $item, array $requestedAffixIds): bool
    {
        $requestedAffixIds = array_values(array_filter($requestedAffixIds, fn ($affixId) => ! is_null($affixId)));

        if (empty($requestedAffixIds)) {
            return false;
        }

        $affixesById = ItemAffix::whereIn('id', $requestedAffixIds)->get()->keyBy('id');

        foreach ($requestedAffixIds as $affixId) {
            $affix = $affixesById->get($affixId);

            if (is_null($affix)) {
                return false;
            }

            if ($affix->type === 'prefix' && (int) $item->item_prefix_id !== (int) $affixId) {
                return false;
            }

            if ($affix->type === 'suffix' && (int) $item->item_suffix_id !== (int) $affixId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes an uncommitted Batch Crafting item that failed the atomic affix
     * requirement. Never deletes a committed, player-owned item: confirms the item is
     * not referenced by any Inventory, Set, Alchemy Bag, Gem Bag or Market Board slot
     * before deleting.
     */
    private function discardPartialBatchItem(Item $item): void
    {
        if (InventorySlot::where('item_id', $item->id)->exists()) {
            return;
        }

        if (SetSlot::where('item_id', $item->id)->exists()) {
            return;
        }

        if (AlchemyBagSlot::where('item_id', $item->id)->exists()) {
            return;
        }

        if (GemBagSlot::where('gem_id', $item->id)->exists()) {
            return;
        }

        if (MarketBoard::where('item_id', $item->id)->exists()) {
            return;
        }

        $item->delete();
    }

    /**
     * Strips an unfulfilled kept/disposition state off a single action so an
     * intermediate KEEP result (e.g. the crafting step of Craft and Enchant Experience)
     * never leaks kept_count or a kept_item into a final result. Never touches an action
     * that already has a real slot_id/set_slot_id, never marks the action failed, and
     * preserves crafted_item and any existing failure info.
     */
    private function removeIntermediateKeptOutput(array &$result, int $actionIndex): void
    {
        if (! isset($result['actions'][$actionIndex])) {
            return;
        }

        if (isset($result['counts']['kept_count'])) {
            $result['counts']['kept_count'] = max(0, ((int) $result['counts']['kept_count']) - 1);

            if ($result['counts']['kept_count'] === 0) {
                unset($result['counts']['kept_count']);
            }
        }

        unset(
            $result['actions'][$actionIndex]['kept_item'],
            $result['actions'][$actionIndex]['disposition'],
            $result['actions'][$actionIndex]['destination_set'],
        );
        $result['kept_output_committed'] = false;
    }

    private function craftForResolvedDestination(
        BatchCrafting $batchCrafting,
        Character $character,
        Item $item,
        string $craftingType,
        BatchCraftingDisposition $disposition,
    ): array {
        if (! $this->shouldCommitKeptOutput($disposition)) {
            return $this->craftingService->craftForBatch($character, $item, $craftingType);
        }

        try {
            $result = $this->craftingService->craftForBatch(
                $character,
                $item,
                $craftingType,
                true,
                $this->resolvedDestinationCreator($batchCrafting, $character),
            );
        } catch (\RuntimeException) {
            return ['success' => false, 'item' => null, 'reason' => 'destination_failed', 'destination' => null];
        }

        if (($result['success'] ?? false) && is_null($result['destination'] ?? null) && isset($result['item'])) {
            $this->discardPartialBatchItem($result['item']);

            return ['success' => false, 'item' => null, 'reason' => 'destination_failed', 'destination' => null];
        }

        return $result;
    }

    private function resolvedDestinationCreator(BatchCrafting $batchCrafting, Character $character): callable
    {
        return function (Item $item) use ($batchCrafting, $character): ?array {
            $progress = $batchCrafting->fresh()->progress ?? [];
            $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
            $craftMode = $progress['craft_mode'] ?? 'experience';
            $supportsSelectedDestination = $disposition === BatchCraftingDisposition::KEEP
                && in_array($batchCrafting->batch_type, [BatchCraftingType::CRAFT->value, BatchCraftingType::CRAFT_AND_ENCHANT->value], true)
                && in_array($craftMode, ['specific_item', 'craft_set', 'craft_enchant_set'], true);
            $destination = $supportsSelectedDestination
                ? ($progress['output_destination'] ?? 'crafted_items_set')
                : 'crafted_items_set';

            if ($destination === 'inventory') {
                $inventory = Inventory::where('character_id', $character->id)->lockForUpdate()->first();

                if (is_null($inventory) || $character->refresh()->isInventoryFull()) {
                    return null;
                }

                $slot = $inventory->slots()->create([
                    'inventory_id' => $inventory->id,
                    'item_id' => $item->id,
                ]);

                return [
                    'destination' => 'inventory',
                    'destination_label' => 'Inventory',
                    'slot_id' => $slot->id,
                    'set_slot_id' => null,
                ];
            }

            if ($destination === 'inventory_set') {
                $set = InventorySet::where('id', (int) ($progress['output_set_id'] ?? 0))
                    ->where('character_id', $character->id)
                    ->lockForUpdate()
                    ->first();

                if (is_null($set) || $set->isBatchCraftingSet() || $set->is_equipped || $set->remainingSlots() < 1) {
                    return null;
                }

                $setSlot = $this->inventorySetService->putItemIntoSet($set, $item);

                if (is_null($setSlot)) {
                    return null;
                }

                return [
                    'destination' => 'inventory_set',
                    'destination_label' => $set->name,
                    'slot_id' => null,
                    'set_slot_id' => $setSlot->id,
                ];
            }

            $created = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $item);

            if (! $created['success']) {
                return null;
            }

            return [
                'destination' => 'crafted_items_set',
                'destination_label' => InventorySet::BATCH_CRAFTING_SET_NAME,
                'slot_id' => null,
                'set_slot_id' => $created['set_slot']->id,
            ];
        };
    }

    private function dispositionForCraftResult(
        BatchCrafting $batchCrafting,
        Character $character,
        Item $item,
        BatchCraftingDisposition $disposition,
        ?array $destination,
    ): array {
        if (! $this->shouldCommitKeptOutput($disposition)) {
            return $this->applyDispositionForItem($batchCrafting, $character, $item, $disposition);
        }

        if (is_null($destination)) {
            return [
                'counts' => ['failed_count' => 1],
                'details' => [
                    'status' => 'failed',
                    'failure' => 'The retained item could not be created in its selected destination.',
                ],
            ];
        }

        if ($disposition !== BatchCraftingDisposition::KEEP) {
            $dispositionResult = $this->applyDispositionForItem($batchCrafting, $character, $item, $disposition);

            if (($dispositionResult['counts']['kept_count'] ?? 0) < 1) {
                $this->removeDestinationItem($destination);

                return $dispositionResult;
            }

            $itemDetails = $dispositionResult['details']['kept_item'] ?? $this->itemDetails($item);
            $existingSetSlotId = $itemDetails['set_slot_id'] ?? null;

            if (! is_null($existingSetSlotId) && (int) $existingSetSlotId !== (int) ($destination['set_slot_id'] ?? 0)) {
                $this->removeDestinationItem($destination);

                return $dispositionResult;
            }

            $itemDetails['slot_id'] = $destination['slot_id'] ?? null;
            $itemDetails['set_slot_id'] = $destination['set_slot_id'] ?? null;
            $dispositionResult['details']['kept_item'] = $itemDetails;
            $dispositionResult['details']['destination_set'] = $destination['destination_label'] ?? null;
            $this->rememberCommittedKeepBestSlot(
                $batchCrafting,
                $item,
                $destination['set_slot_id'] ?? $destination['slot_id'] ?? 0,
            );

            event(new UpdateCharacterInventoryCountEvent($character));

            return $dispositionResult;
        }

        $itemDetails = $this->itemDetails(
            $item,
            $destination['slot_id'] ?? $destination['set_slot_id'] ?? null,
            ($destination['destination'] ?? null) === 'inventory',
        );

        if (! is_null($destination['slot_id'] ?? null)) {
            $itemDetails['slot_id'] = $destination['slot_id'];
        }

        if (! is_null($destination['set_slot_id'] ?? null)) {
            $itemDetails['set_slot_id'] = $destination['set_slot_id'];
        }

        $linkId = $destination['slot_id'] ?? $destination['set_slot_id'];
        $linkType = match ($destination['destination'] ?? null) {
            'inventory' => 'inventory',
            'inventory_set' => 'inventory_set',
            default => 'crafted_items_set',
        };
        $itemName = $item->affix_name ?? $item->name;
        $affixNames = collect([$item->itemPrefix?->name, $item->itemSuffix?->name])->filter()->values()->all();
        $successMessage = empty($affixNames)
            ? 'You crafted a: ' . $itemName . '!'
            : 'Applied enchantment: ' . implode(', ', $affixNames) . ' to: ' . $itemName;
        $keptMessage = match ($destination['destination'] ?? null) {
            'inventory' => 'Kept: ' . $itemName . ' in your Inventory.',
            'inventory_set' => 'Kept: ' . $itemName . ' in: ' . ($destination['destination_label'] ?? 'the selected set') . '.',
            default => 'Kept: ' . $itemName . ' in your Crafted Items Set.',
        };

        $this->serverMessageHandler->sendBasicMessageWithLink(
            $character->user,
            $successMessage,
            $linkId,
            $linkType,
            $itemName,
        );
        $this->serverMessageHandler->sendBasicMessageWithLink(
            $character->user,
            $keptMessage,
            $linkId,
            $linkType,
            $itemName,
        );

        event(new UpdateCharacterInventoryCountEvent($character));

        return [
            'counts' => ['kept_count' => 1],
            'details' => [
                'disposition' => 'keep',
                'kept_item' => $itemDetails,
                'destination_set' => $destination['destination_label'] ?? null,
                'created_in_crafted_items_set' => ($destination['destination'] ?? null) === 'crafted_items_set',
            ],
        ];
    }

    private function destinationFromResult(array $result): ?array
    {
        $keptItem = $result['actions'][0]['kept_item'] ?? null;

        if (! is_array($keptItem)) {
            return null;
        }

        if (! is_null($keptItem['slot_id'] ?? null)) {
            return ['destination' => 'inventory', 'slot_id' => (int) $keptItem['slot_id'], 'set_slot_id' => null];
        }

        if (! is_null($keptItem['set_slot_id'] ?? null)) {
            return ['destination' => 'inventory_set', 'slot_id' => null, 'set_slot_id' => (int) $keptItem['set_slot_id']];
        }

        return null;
    }

    private function replaceDestinationItem(?array $destination, Item $item): void
    {
        if (is_null($destination)) {
            return;
        }

        if (! is_null($destination['slot_id'] ?? null)) {
            InventorySlot::where('id', $destination['slot_id'])->update(['item_id' => $item->id]);

            return;
        }

        SetSlot::where('id', $destination['set_slot_id'] ?? 0)->update(['item_id' => $item->id]);
    }

    private function removeDestinationItem(?array $destination): void
    {
        if (is_null($destination)) {
            return;
        }

        if (! is_null($destination['slot_id'] ?? null)) {
            InventorySlot::where('id', $destination['slot_id'])->delete();

            return;
        }

        SetSlot::where('id', $destination['set_slot_id'] ?? 0)->delete();
    }

    private function rememberCommittedKeepBestSlot(BatchCrafting $batchCrafting, Item $item, int $setSlotId): void
    {
        $disposition = BatchCraftingDisposition::tryFrom($batchCrafting->disposition);

        if (! $this->isKeepBestDisposition($disposition)) {
            return;
        }

        $this->rememberKeepBestReference($batchCrafting, $item, $disposition->value, $setSlotId);
    }

    private function rememberKeepBestReference(BatchCrafting $batchCrafting, Item $item, string $disposition, ?int $setSlotId): void
    {
        $progress = $batchCrafting->fresh()->progress ?? [];
        $itemType = $item->type ?? 'weapon';
        $keepBestItems = $progress['keep_best_item_ids'] ?? [];
        $keepBestReferences = $progress['keep_best_item_refs'] ?? [];

        $keepBestItems[$itemType] = $item->id;
        $keepBestReferences[$itemType] = [
            'item_id' => $item->id,
            'set_slot_id' => $setSlotId,
            'item_type' => $itemType,
            'disposition' => $disposition,
        ];

        $batchCrafting->update([
            'progress' => array_merge($progress, [
                'keep_best_item_ids' => $keepBestItems,
                'keep_best_item_refs' => $keepBestReferences,
            ]),
        ]);
    }

    private function isKeepBestDisposition(?BatchCraftingDisposition $disposition): bool
    {
        return in_array($disposition, [
            BatchCraftingDisposition::KEEP_BEST_SELL_REST,
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST,
        ], true);
    }

    private function pickEnchantableSlot(Character $character): ?InventorySlot
    {
        if (is_null($character->inventory)) {
            return null;
        }

        return $character->inventory->slots()
            ->where('equipped', false)
            ->whereHas('item', function ($query) {
                $query->whereNotIn('type', ['trinket', 'artifact', 'alchemy', 'gem', 'quest'])
                    ->whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id');
            })
            ->first();
    }

    private function resolveIntendedAffixIdsForEnchant(Character $character, ?array $affixIds = null): array
    {
        $affixIds = array_values(array_filter($affixIds ?? [], fn ($affixId) => ! is_null($affixId)));

        if (! empty($affixIds)) {
            return $affixIds;
        }

        $enchantingSkill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->first();

        if (is_null($enchantingSkill)) {
            return [];
        }

        $resolvedAffixIds = [];

        $prefix = ItemAffix::where('type', 'prefix')
            ->where('skill_level_required', '<=', $enchantingSkill->level)
            ->where('skill_level_trivial', '>=', $enchantingSkill->level)
            ->orderByDesc('skill_level_required')
            ->orderBy('id')
            ->first();

        if (! is_null($prefix)) {
            $resolvedAffixIds[] = $prefix->id;
        }

        $suffix = ItemAffix::where('type', 'suffix')
            ->where('skill_level_required', '<=', $enchantingSkill->level)
            ->where('skill_level_trivial', '>=', $enchantingSkill->level)
            ->orderByDesc('skill_level_required')
            ->orderBy('id')
            ->first();

        if (! is_null($suffix)) {
            $resolvedAffixIds[] = $suffix->id;
        }

        return $resolvedAffixIds;
    }

    private function intBlockedForEnchant(Character $character, ?array $affixIds = null): bool
    {
        $resolvedAffixIds = array_values(array_filter($affixIds ?? [], fn ($affixId) => ! is_null($affixId)));

        if (empty($resolvedAffixIds)) {
            return false;
        }

        $characterInt = $character->getInformation()->statMod('int');
        $selectedAffixes = ItemAffix::whereIn('id', $resolvedAffixIds)->get();

        return $selectedAffixes->contains(fn (ItemAffix $affix) => $characterInt < $affix->int_required);
    }

    private function tryEnchantSlot(Character $character, InventorySlot $slot, ?array $affixIds = null): bool
    {
        $resolvedAffixIds = $this->resolveIntendedAffixIdsForEnchant($character, $affixIds);

        if (empty($resolvedAffixIds)) {
            return false;
        }

        if ($this->intBlockedForEnchant($character, $resolvedAffixIds)) {
            return false;
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $resolvedAffixIds, $slot->item_id);

        if ($character->gold < $cost) {
            return false;
        }

        $this->enchantingService->enchant($character, [
            'affix_ids' => $resolvedAffixIds,
            'enchant_for_event' => false,
        ], $slot, $cost);

        return true;
    }

    /**
     * Try to enchant a Batch Crafting item directly, with no InventorySlot involved.
     *
     * Mirrors tryEnchantSlot()'s gating (resolve affixes, INT block, affordability)
     * but calls EnchantingService::enchantItemForBatch(). 'attempted' is false when
     * the enchant was never tried; when true, a null 'item' means it was destroyed.
     */
    private function tryEnchantItem(Character $character, Item $item, ?array $affixIds = null, bool $suppressSuccessServerMessage = false): array
    {
        $resolvedAffixIds = $this->resolveIntendedAffixIdsForEnchant($character, $affixIds);

        if (empty($resolvedAffixIds)) {
            return ['attempted' => false, 'item' => null, 'affix_names' => [], 'affix_ids' => []];
        }

        if ($this->intBlockedForEnchant($character, $resolvedAffixIds)) {
            return ['attempted' => false, 'item' => null, 'affix_names' => [], 'affix_ids' => []];
        }

        $cost = $this->enchantingService->getCostOfEnchantment($character, $resolvedAffixIds, $item->id);

        if ($character->gold < $cost) {
            return ['attempted' => false, 'item' => null, 'affix_names' => [], 'affix_ids' => []];
        }

        $affixesById = ItemAffix::whereIn('id', $resolvedAffixIds)->get()->keyBy('id');
        $affixNames = collect($resolvedAffixIds)
            ->map(fn (int $affixId) => $affixesById->get($affixId)?->name)
            ->filter()
            ->values()
            ->all();
        $enchantResult = $this->enchantingService->enchantItemForBatch($character, $item, $resolvedAffixIds, $cost, $suppressSuccessServerMessage);

        return ['attempted' => true, 'item' => $enchantResult['success'] ? $enchantResult['item'] : null, 'affix_names' => $affixNames, 'affix_ids' => $resolvedAffixIds];
    }

    private function applyDispositionForItem(BatchCrafting $batchCrafting, Character $character, Item $item, BatchCraftingDisposition $disposition): array
    {
        return match ($disposition) {
            BatchCraftingDisposition::KEEP => [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($item)],
            ],
            BatchCraftingDisposition::KEEP_HIGHEST => $this->applyKeepHighestItem($batchCrafting, $character, $item),
            BatchCraftingDisposition::SELL => $this->sellItem($character, $item),
            BatchCraftingDisposition::DESTROY => $this->destroyItem($character, $item),
            BatchCraftingDisposition::LIST => $this->listItem($batchCrafting, $character, $item),
            BatchCraftingDisposition::DISENCHANT => $this->disenchantItem($character, $item),
            BatchCraftingDisposition::KEEP_BEST_SELL_REST => $this->applyKeepBestAndRestItem($batchCrafting, $character, $item, 'sell'),
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST => $this->applyKeepBestAndRestItem($batchCrafting, $character, $item, 'disenchant'),
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST => $this->applyKeepBestAndRestItem($batchCrafting, $character, $item, 'destroy'),
            BatchCraftingDisposition::USE_NOW => [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep', 'kept_item' => $this->itemDetails($item)],
            ],
        };
    }

    /**
     * Loser action naming here (sell/destroy/disenchant) matches the player-facing
     * disposition labels: Keep Best and Sell/Destroy/Disenchant Rest.
     */
    private function applyKeepBestAndRestItem(BatchCrafting $batchCrafting, Character $character, Item $newItem, string $loserAction): array
    {
        $itemType = $newItem->type ?? 'weapon';
        $newLevel = (int) ($newItem->skill_level_required ?? 0);
        $progress = $batchCrafting->progress ?? [];
        $keepBestItems = $progress['keep_best_item_ids'] ?? [];
        $keepBestReferences = $progress['keep_best_item_refs'] ?? [];
        $trackedReference = $keepBestReferences[$itemType] ?? [];
        $trackedItemId = $trackedReference['item_id'] ?? $keepBestItems[$itemType] ?? null;
        $trackedSetSlotId = $trackedReference['set_slot_id'] ?? null;
        $trackedSetSlot = is_null($trackedSetSlotId)
            ? null
            : SetSlot::with(['item', 'inventorySet'])->find($trackedSetSlotId);

        if (! is_null($trackedSetSlot) && (int) ($trackedSetSlot->inventorySet?->character_id ?? 0) !== (int) $character->id) {
            $trackedSetSlot = null;
            $trackedSetSlotId = null;
        }

        $trackedItem = $trackedSetSlot?->item ?? (is_null($trackedItemId) ? null : Item::find($trackedItemId));
        $keepBestDisposition = 'keep_best_' . $loserAction . '_rest';

        // Enchanting dedupes identical base+affix combinations onto the same Item row,
        // so re-crafting the same item type can hand back the exact item already
        // tracked as best. Treat that as "still the best, nothing changed" instead of
        // selling/destroying/disenchanting the retained slot and orphaning the item.
        if (! is_null($trackedItem) && (int) $trackedItem->id === (int) $newItem->id) {
            $keptItemDetails = $this->itemDetails($newItem, $trackedSetSlotId);
            $keptItemDetails['set_slot_id'] = $trackedSetSlotId;

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => $keepBestDisposition, 'kept_item' => $keptItemDetails],
            ];
        }

        if (is_null($trackedItem)) {
            $this->rememberKeepBestReference($batchCrafting, $newItem, $keepBestDisposition, null);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => $keepBestDisposition, 'kept_item' => $this->itemDetails($newItem)],
            ];
        }

        $trackedLevel = (int) ($trackedItem->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $loserResult = $this->applyKeepBestLoserAction($character, $trackedItem, $loserAction, $trackedSetSlotId);
            $this->rememberKeepBestReference($batchCrafting, $newItem, $keepBestDisposition, null);

            return [
                'counts' => $this->mergeCounts(['kept_count' => 1], $loserResult['counts']),
                'details' => array_merge(['disposition' => $keepBestDisposition, 'kept_item' => $this->itemDetails($newItem)], $loserResult['details']),
            ];
        }

        $loserResult = $this->applyKeepBestLoserAction($character, $newItem, $loserAction, null);

        return [
            'counts' => $loserResult['counts'],
            'details' => array_merge(['disposition' => $keepBestDisposition], $loserResult['details']),
        ];
    }

    private function applyKeepBestLoserAction(Character $character, Item $item, string $loserAction, ?int $setSlotId): array
    {
        if (! is_null($setSlotId)) {
            $setSlot = SetSlot::with(['item', 'inventorySet'])->find($setSlotId);

            if (! is_null($setSlot) && ! is_null($setSlot->item) && ! is_null($setSlot->inventorySet) && (int) $setSlot->inventorySet->character_id === (int) $character->id) {
                $itemDetails = $this->itemDetails($setSlot->item, $setSlot->id);
                $setSlotItem = $setSlot->item;

                return match ($loserAction) {
                    'disenchant' => $this->disenchantKeepBestSetSlot($character, $setSlot->inventorySet, $setSlot->id, $itemDetails),
                    'destroy' => $this->destroyKeepBestSetSlot($character, $setSlot->inventorySet, $setSlot->id, $itemDetails, $setSlotItem),
                    default => $this->sellKeepBestSetSlot($character, $setSlot->inventorySet, $setSlot->id, $itemDetails, $setSlotItem),
                };
            }
        }

        return match ($loserAction) {
            'disenchant' => $this->disenchantItem($character, $item),
            'destroy' => $this->destroyItem($character, $item),
            default => $this->sellItem($character, $item),
        };
    }

    private function sellKeepBestSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails, Item $item): array
    {
        $goldBefore = $character->gold;
        $this->multiInventoryActionService->sellManySetSlots($character, $set, [$setSlotId]);
        $this->discardBatchClonedItemIfOrphaned($item);
        $goldGained = max(0, $character->refresh()->gold - $goldBefore);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyKeepBestSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails, Item $item): array
    {
        $this->multiInventoryActionService->destroyManySetSlots($character, $set, [$setSlotId]);
        $this->discardBatchClonedItemIfOrphaned($item);

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function disenchantKeepBestSetSlot(Character $character, InventorySet $set, int $setSlotId, ?array $itemDetails): array
    {
        $goldDustBefore = $character->gold_dust;
        $this->multiInventoryActionService->disenchantManySetSlots($character, $set, [$setSlotId]);
        $goldDustGained = max(0, $character->refresh()->gold_dust - $goldDustBefore);

        return [
            'counts' => ['disenchanted_count' => 1],
            'details' => ['disposition' => 'disenchant', 'disenchanted_item' => $this->removedItemDetails($itemDetails, 'disenchanted'), 'gold_dust_gained' => $goldDustGained],
        ];
    }

    private function applyKeepHighestItem(BatchCrafting $batchCrafting, Character $character, Item $newItem): array
    {
        $itemType = $newItem->type ?? 'weapon';
        $newLevel = (int) ($newItem->skill_level_required ?? 0);

        $progress = $batchCrafting->progress ?? [];
        $keepHighestItems = $progress['keep_highest_item_ids'] ?? [];
        $trackedItemId = $keepHighestItems[$itemType] ?? null;
        $trackedItem = is_null($trackedItemId) ? null : Item::find($trackedItemId);

        if (is_null($trackedItem)) {
            $keepHighestItems[$itemType] = $newItem->id;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_highest_item_ids' => $keepHighestItems])]);

            return [
                'counts' => ['kept_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($newItem)],
            ];
        }

        $trackedLevel = (int) ($trackedItem->skill_level_required ?? 0);

        if ($newLevel >= $trackedLevel) {
            $sold = $this->sellItem($character, $trackedItem);
            $keepHighestItems[$itemType] = $newItem->id;
            $batchCrafting->update(['progress' => array_merge($progress, ['keep_highest_item_ids' => $keepHighestItems])]);

            return [
                'counts' => ['kept_count' => 1, 'sold_count' => 1],
                'details' => ['disposition' => 'keep_highest', 'kept_item' => $this->itemDetails($newItem), 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
            ];
        }

        $sold = $this->sellItem($character, $newItem);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'keep_highest', 'sold_item' => $sold['details']['sold_item'] ?? null, 'gold_gained' => $sold['details']['gold_gained'] ?? 0],
        ];
    }

    private function sellItem(Character $character, Item $item): array
    {
        $itemDetails = $this->itemDetails($item);
        $goldGained = max(0, (int) SellItemCalculator::fetchSalePriceWithAffixes($item));
        $itemName = $item->affix_name ?? $item->name;

        $character->increment('gold', $goldGained);
        $this->discardBatchClonedItemIfOrphaned($item);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Sold: ' . $itemName . ' for: ' . number_format($goldGained) . ' Gold.');

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyItem(Character $character, Item $item): array
    {
        $itemDetails = $this->itemDetails($item);
        $itemName = $item->affix_name ?? $item->name;

        $this->discardBatchClonedItemIfOrphaned($item);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Destroyed: ' . $itemName . '.');

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    /**
     * Dispatches the same DisenchantMany job the existing InventorySlot/SetSlot
     * disenchant paths use. The item is intentionally never deleted here: the
     * job runs on the queue and still needs to load it by id.
     */
    private function disenchantItem(Character $character, Item $item): array
    {
        $itemDetails = $this->itemDetails($item);
        $goldDustBefore = $character->gold_dust;

        DisenchantMany::dispatch($character, [$item->id]);

        $goldDustGained = max(0, $character->refresh()->gold_dust - $goldDustBefore);

        return [
            'counts' => ['disenchanted_count' => 1],
            'details' => [
                'disposition' => 'disenchant',
                'disenchanted_item' => $this->removedItemDetails($itemDetails, 'disenchanted'),
                'gold_dust_gained' => $goldDustGained,
            ],
        ];
    }

    /**
     * Uses the player-supplied listing price from progress.listing_price (set at
     * batch start, matching the manual List Item price the player was shown), floored
     * to the item's min sale price so the same rule manual listing enforces still applies.
     */
    private function listItem(BatchCrafting $batchCrafting, Character $character, Item $item): array
    {
        $requestedPrice = (int) (($batchCrafting->progress ?? [])['listing_price'] ?? 1);
        $minPrice = (int) SellItemCalculator::fetchMinPrice($item);
        $price = max($requestedPrice, $minPrice, 1);

        MarketBoard::create([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'listed_price' => $price,
        ]);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Listed: ' . ($item->affix_name ?? $item->name) . ' for: ' . number_format($price) . ' Gold.');

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($this->itemDetails($item), 'listed'), 'listed_price' => $price],
        ];
    }

    /**
     * Batch crafted items never sit in an InventorySlot/SetSlot until they are kept, so a
     * non-keep disposition (sell/destroy/disenchant) must clean up an enchanted clone here
     * instead of relying on a slot delete. Base catalog items (no prefix/suffix) are never
     * deleted since other slots/characters may reference the same catalog row.
     */
    private function discardBatchClonedItemIfOrphaned(Item $item): void
    {
        if (is_null($item->item_prefix_id) && is_null($item->item_suffix_id)) {
            return;
        }

        if ($item->inventorySlots()->doesntExist()
            && $item->inventorySetSlots()->doesntExist()
            && $item->marketListings()->doesntExist()
            && $item->marketHistory()->doesntExist()) {
            $item->delete();
        }
    }

    private function findProducedAlchemySlot(Character $character, array $amountsBefore): ?AlchemyBagSlot
    {
        return AlchemyBagSlot::where('character_id', $character->id)
            ->with('item')
            ->get()
            ->first(function (AlchemyBagSlot $slot) use ($amountsBefore) {
                return $slot->amount > ($amountsBefore[$slot->item_id] ?? 0);
            });
    }

    private function sellAlchemySlot(Character $character, AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id);
        $goldGained = (int) SellItemCalculator::fetchSalePriceWithAffixes($slot->item);

        $character->update(['gold' => $character->gold + $goldGained]);
        $this->decrementAlchemySlot($slot);

        return [
            'counts' => ['sold_count' => 1],
            'details' => ['disposition' => 'sell', 'sold_item' => $this->removedItemDetails($itemDetails, 'sold'), 'gold_gained' => $goldGained],
        ];
    }

    private function destroyAlchemySlot(AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id);
        $this->decrementAlchemySlot($slot);

        return [
            'counts' => ['destroyed_count' => 1],
            'details' => ['disposition' => 'destroy', 'destroyed_item' => $this->removedItemDetails($itemDetails, 'destroyed')],
        ];
    }

    private function listAlchemySlot(BatchCrafting $batchCrafting, Character $character, AlchemyBagSlot $slot): array
    {
        $itemDetails = $this->itemDetails($slot->item, $slot->id);
        $requestedPrice = (int) (($batchCrafting->progress ?? [])['listing_price'] ?? 1);
        $minPrice = (int) SellItemCalculator::fetchMinPrice($slot->item);
        $price = max($requestedPrice, $minPrice, 1);

        MarketBoard::create([
            'character_id' => $character->id,
            'item_id' => $slot->item_id,
            'listed_price' => $price,
        ]);

        $this->decrementAlchemySlot($slot);

        return [
            'counts' => ['listed_count' => 1],
            'details' => ['disposition' => 'list', 'listed_item' => $this->removedItemDetails($itemDetails, 'listed'), 'listed_price' => $price],
        ];
    }

    private function decrementAlchemySlot(AlchemyBagSlot $slot): void
    {
        if ($slot->amount <= 1) {
            $slot->delete();

            return;
        }

        $slot->update(['amount' => $slot->amount - 1]);
    }

    private function itemDetails(?Item $item, ?int $slotId = null, bool $canView = true, ?int $alchemySlotId = null): ?array
    {
        if (is_null($item)) {
            return null;
        }

        return [
            'slot_id' => $slotId,
            'alchemy_slot_id' => $alchemySlotId,
            'item_id' => $item->id,
            'item_id_for_modal' => $canView ? $item->id : null,
            'slot_id_for_modal' => $canView ? $slotId : null,
            'name' => $item->affix_name ?? $item->name,
            'affix_name' => $item->affix_name,
            'type' => $item->type,
            'description' => $item->description,
            'crafting_type' => $item->crafting_type,
            'skill_level_required' => $item->skill_level_required,
            'base_damage' => $item->base_damage ?? 0,
            'base_ac' => $item->base_ac ?? 0,
            'base_healing' => $item->base_healing ?? 0,
            'str_modifier' => $item->str_modifier ?? 0,
            'dex_modifier' => $item->dex_modifier ?? 0,
            'agi_modifier' => $item->agi_modifier ?? 0,
            'chr_modifier' => $item->chr_modifier ?? 0,
            'dur_modifier' => $item->dur_modifier ?? 0,
            'int_modifier' => $item->int_modifier ?? 0,
            'focus_modifier' => $item->focus_modifier ?? 0,
            'skill_name' => $item->skill_name ?? null,
            'skill_bonus' => $item->skill_bonus ?? 0,
            'skill_training_bonus' => $item->skill_training_bonus ?? 0,
            'item_prefix' => $item->itemPrefix?->name,
            'item_suffix' => $item->itemSuffix?->name,
            'sockets' => [],
            'holy_stacks' => $item->holy_stacks ?? 0,
            'affix_count' => $item->affix_count ?? 0,
            'is_unique' => (bool) ($item->is_unique ?? false),
            'holy_stacks_applied' => $item->holy_stacks_applied ?? 0,
            'is_mythic' => (bool) ($item->is_mythic ?? false),
            'is_cosmic' => (bool) ($item->is_cosmic ?? false),
            'can_view' => $canView && ! is_null($slotId),
            'crafted_at' => now()->toJSON(),
            'full_item_details' => (new ItemTransformer())->transform($item),
        ];
    }

    private function craftingSkillSnapshot(Character $character, string $craftingType): ?array
    {
        return $this->skillSnapshot($character, $this->craftingSkillName($craftingType), false);
    }

    private function craftingSkillName(string $craftingType): string
    {
        if (in_array($craftingType, ['dagger', 'sword', 'claw', 'wand', 'censer', 'stave', 'hammer', 'bow', 'gun', 'fan', 'mace', 'scratch-awl', 'weapon'], true)) {
            return 'Weapon Crafting';
        }

        return match ($craftingType) {
            'armour', 'helmet', 'body', 'leggings', 'sleeves', 'gloves', 'shield', 'feet' => 'Armour Crafting',
            'ring' => 'Ring Crafting',
            'spell', 'spell_damage', 'spell_healing' => 'Spell Crafting',
            default => 'Weapon Crafting',
        };
    }

    private function skillSnapshot(Character $character, string $lookup, bool $byType): ?array
    {
        $skill = Skill::where('character_id', $character->id)
            ->whereHas('baseSkill', function ($query) use ($lookup, $byType) {
                if ($byType) {
                    $query->where('type', $lookup);

                    return;
                }

                $query->where('name', $lookup);
            })
            ->with('baseSkill')
            ->first();

        if (is_null($skill)) {
            return null;
        }

        return [
            'level' => (int) $skill->level,
            'xp' => (int) $skill->xp,
            'xp_max' => (int) $skill->xp_max,
        ];
    }

    private function skillExperienceGained(?array $before, ?array $after): int
    {
        if (is_null($before) || is_null($after)) {
            return 0;
        }

        if ($after['level'] > $before['level']) {
            return max(0, ((int) $before['xp_max'] - (int) $before['xp']) + (int) $after['xp']);
        }

        return max(0, (int) $after['xp'] - (int) $before['xp']);
    }

    private function acceptedEventEnchantExperience(Character $character, InventorySlot|GlobalEventCraftingInventorySlot $slot): int
    {
        $remainingSlot = $slot instanceof GlobalEventCraftingInventorySlot
            ? GlobalEventCraftingInventorySlot::find($slot->id)
            : InventorySlot::find($slot->id);

        if (! is_null($remainingSlot)) {
            return 0;
        }

        $skill = Skill::where('character_id', $character->id)
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->with('baseSkill')
            ->first();

        if (is_null($skill)) {
            return 0;
        }

        $xp = SkillXPCalculator::fetchSkillXP($skill);
        $gameMapBonus = $character->map?->gameMap?->skill_training_bonus ?? 0;

        return (int) floor($xp + ($xp * $gameMapBonus));
    }

    private function removedItemDetails(?array $itemDetails, string $status): ?array
    {
        if (is_null($itemDetails)) {
            return null;
        }

        return array_merge($itemDetails, [
            'status' => $status,
            'can_view' => false,
            'item_id_for_modal' => null,
            'slot_id_for_modal' => null,
        ]);
    }

    private function currencyDetails(array $before, Character $character): array
    {
        return [
            'before' => $before,
            'current' => [
                'gold' => $character->gold,
                'gold_dust' => $character->gold_dust,
                'shards' => $character->shards,
            ],
        ];
    }

    private function mergeCounts(array $counts, array $additions): array
    {
        foreach ($additions as $column => $increment) {
            $counts[$column] = ($counts[$column] ?? 0) + $increment;
        }

        return $counts;
    }
}
