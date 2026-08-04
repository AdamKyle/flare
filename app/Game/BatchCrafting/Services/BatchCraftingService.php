<?php

namespace App\Game\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\SetSlot;
use App\Flare\Models\Skill;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Values\AutomationType;
use App\Game\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Validations\SetHandsValidation;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class BatchCraftingService
{
    public const DURATION_HOURS = 8;

    public const ITEMS_PER_RECURRING_TICK = 6;

    public const ITEMS_PER_FULL_SET = 23;

    public const FULL_SETS_PER_EXPERIENCE_TICK = 1;

    public const ITEMS_PER_EXPERIENCE_TICK = 6;

    public const EVENT_ITEMS_PER_SET_TICK = 23;

    public const RECURRING_DELAY_SECONDS = 60;

    public const IMMEDIATE_DELAY_SECONDS = 2;

    public const INITIAL_FINITE_DELAY_SECONDS = 60;

    public static function eventCraftQueue(): array
    {
        return [
            ['type' => 'weapon', 'crafting_type' => 'weapon'],
            ['type' => 'armour', 'crafting_type' => 'armour'],
            ['type' => 'ring', 'crafting_type' => 'ring'],
            ['type' => ItemType::SPELL_DAMAGE->value, 'crafting_type' => 'spell'],
            ['type' => ItemType::SPELL_HEALING->value, 'crafting_type' => 'spell'],
        ];
    }

    private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService;

    private readonly GlobalEventGoalProgressionService $globalEventGoalProgressionService;

    private readonly MonitoredBugReportService $monitoredBugReportService;

    private readonly SetHandsValidation $setHandsValidation;

    public function __construct(
        private readonly BatchCraftingProcessor $processor,
        private readonly CraftingService $craftingService,
        private readonly BatchCraftingLogger $batchCraftingLogger,
        private readonly EnchantingService $enchantingService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly HolyItemService $holyItemService,
        private readonly ItemTransformer $itemTransformer,
        ?GlobalEventGoalEligibilityService $globalEventGoalEligibilityService = null,
        ?GlobalEventGoalProgressionService $globalEventGoalProgressionService = null,
        ?MonitoredBugReportService $monitoredBugReportService = null,
        ?SetHandsValidation $setHandsValidation = null,
    ) {
        $this->globalEventGoalEligibilityService = $globalEventGoalEligibilityService ?? new GlobalEventGoalEligibilityService();
        $this->globalEventGoalProgressionService = $globalEventGoalProgressionService ?? new GlobalEventGoalProgressionService(new EventGoalsService());
        $this->monitoredBugReportService = $monitoredBugReportService ?? new MonitoredBugReportService();
        $this->setHandsValidation = $setHandsValidation ?? new SetHandsValidation();
    }

    public function start(Character $character, array $data): BatchCrafting
    {
        $type = BatchCraftingType::from($data['batch_type']);
        $disposition = BatchCraftingDisposition::from($data['disposition']);

        if (! $disposition->isAllowedFor($type, $data['progress'] ?? [])) {
            throw ValidationException::withMessages([
                'disposition' => 'This disposition is not allowed for this batch crafting type.',
            ]);
        }

        if ($type === BatchCraftingType::ALCHEMY && $this->isAlchemyLocked($character)) {
            throw ValidationException::withMessages([
                'batch_type' => 'You need to unlock Alchemy before you can batch craft alchemy items.',
            ]);
        }

        if ($type === BatchCraftingType::HOLY_OILS && $this->isAlchemyLocked($character)) {
            throw ValidationException::withMessages([
                'batch_type' => 'You need to unlock Alchemy before you can batch craft with Holy Oils.',
            ]);
        }

        if ($this->active($character)) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting is already running for this character.',
            ]);
        }

        if ($character->currentAutomations()->where('type', AutomationType::FACTION_LOYALTY->value)->where('completed_at', '>', now())->exists()) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start while faction loyalty automation is running.',
            ]);
        }

        if ($character->is_dead) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start while the character is dead.',
            ]);
        }

        if ($type !== BatchCraftingType::TRINKETRY && $this->currencyAmount($character, $type->requiredCurrency()) <= 0) {
            throw ValidationException::withMessages([
                'batch_crafting' => $this->missingRequiredCurrencyMessage($character, $type),
            ]);
        }

        if ($type === BatchCraftingType::HOLY_OILS) {
            $this->validateHolyOilSelections($character, $data);

            if (in_array($disposition, [BatchCraftingDisposition::LIST, BatchCraftingDisposition::DISENCHANT], true)
                && ! $this->holyOilDispositionEligible($character, $data, $disposition)) {
                throw ValidationException::withMessages([
                    'disposition' => $disposition === BatchCraftingDisposition::LIST
                        ? 'Listing is only available when the targeted item(s) already have 1 or 2 enchants.'
                        : 'Disenchanting is only available when the targeted item(s) already have enchants.',
                ]);
            }
        }

        $progress = $this->validatedProgress($character, $type, $data);

        if ($type === BatchCraftingType::HOLY_OILS) {
            $holyOilPlan = $this->holyOilApplicationPlan(
                $character,
                $data['selected_items'] ?? [],
                $data['selected_oils'] ?? [],
                $progress,
            );
            $progress['holy_oil_application_plan'] = $holyOilPlan;
            $progress['holy_oil_application_results'] = [];
            $progress['holy_oil_requested_applications'] = $holyOilPlan['applications_planned'];
            $progress['holy_oil_completed_applications'] = 0;
            $progress['holy_oil_plan_index'] = 0;
        }
        $maximumRequestAmount = $this->maximumRequestAmount($character, $type, $progress, $disposition);
        $requestedAmount = $type === BatchCraftingType::ALCHEMY
            ? (int) ($progress['alchemy_amount'] ?? 0)
            : (int) ($progress['craft_amount'] ?? 0);

        $isAmountMode = ($type === BatchCraftingType::ALCHEMY && ($progress['alchemy_mode'] ?? null) === 'amount')
            || (in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true) && ($progress['craft_mode'] ?? null) === 'specific_item');

        if ($isAmountMode && $requestedAmount > $maximumRequestAmount) {
            throw ValidationException::withMessages([
                ($type === BatchCraftingType::ALCHEMY ? 'progress.alchemy_amount' : 'progress.craft_amount') => 'The requested amount may not exceed '.number_format($maximumRequestAmount).' for the selected destination.',
            ]);
        }

        if ($disposition === BatchCraftingDisposition::LIST
            && $type === BatchCraftingType::ALCHEMY
            && ($progress['alchemy_mode'] ?? null) === 'amount'
            && isset($progress['alchemy_item_id'])) {
            $alchemyItem = Item::find($progress['alchemy_item_id']);
            $minPrice = is_null($alchemyItem) ? 0 : SellItemCalculator::fetchMinPrice($alchemyItem);

            if ($minPrice > 0 && (int) ($data['listing_price'] ?? 0) < $minPrice) {
                throw ValidationException::withMessages([
                    'listing_price' => 'No! The minimum listing price is: '.number_format($minPrice).' Gold.',
                ]);
            }
        }

        $startBlockers = $this->startBlockers($character, $type, $progress, $data['selected_items'] ?? [], $data['selected_oils'] ?? [], $disposition);
        $blockingMessages = array_values(array_map(
            fn (array $blocker): string => $blocker['message'],
            array_filter($startBlockers, fn (array $blocker): bool => $blocker['blocking'])
        ));

        if (! empty($blockingMessages)) {
            throw ValidationException::withMessages([
                'batch_crafting' => $blockingMessages,
            ]);
        }

        $progress['tick_delay_seconds'] = $this->tickDelaySeconds($type, $progress);

        if ($disposition === BatchCraftingDisposition::LIST) {
            $progress['listing_price'] = (int) $data['listing_price'];
        }

        $requiresInitialFiniteDelay = $this->requiresInitialFiniteDelay($type, $progress);
        $continuousFiniteMode = $this->isContinuousFiniteType($type, $progress);
        $firstAttemptAt = now()->addSeconds($requiresInitialFiniteDelay
            ? self::INITIAL_FINITE_DELAY_SECONDS
            : $progress['tick_delay_seconds']);
        $progress = array_merge($progress, match (true) {
            $requiresInitialFiniteDelay => [
                'continuation_active' => true,
                'continuation_state' => 'waiting',
                'continuation_reason' => 'initial_start_delay',
                'continuation_message' => 'Batch Crafting will begin in one moment.',
                'continuation_phase' => 'starting',
                'continuation_item' => null,
                'continuation_delay_seconds' => self::INITIAL_FINITE_DELAY_SECONDS,
                'next_attempt_at' => $firstAttemptAt->toIso8601String(),
            ],
            $continuousFiniteMode => $this->clearedContinuationState(),
            default => [
                'continuation_active' => true,
                'continuation_state' => 'waiting',
                'continuation_reason' => 'continue_remaining_work',
                'continuation_message' => 'Batch Crafting is waiting to begin the first requested attempt.',
                'continuation_phase' => 'starting',
                'continuation_item' => null,
                'continuation_delay_seconds' => $progress['tick_delay_seconds'],
                'next_attempt_at' => $firstAttemptAt->toIso8601String(),
            ],
        });

        $batchCrafting = BatchCrafting::create([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => $type->value,
            'disposition' => $disposition->value,
            'started_at' => now(),
            'ends_at' => now()->addHours(self::DURATION_HOURS),
            'status' => 'running',
            'progress' => $progress,
            'selected_items' => $data['selected_items'] ?? [],
            'selected_oils' => $data['selected_oils'] ?? [],
        ]);

        $this->logger()->batchStarted($batchCrafting, $character);

        $startMessage = $requiresInitialFiniteDelay
            ? 'Batch Crafting is scheduled and will begin in one minute.'
            : 'Batch crafting has started. First action will run in '.$this->firstActionDelayMessage($progress['tick_delay_seconds']).'.';
        event(new ServerMessageEvent($character->user, $startMessage));
        event(new AutomationLogUpdate($character->user_id, $startMessage));
        event(new BatchCraftingStatusUpdated($character->user_id));
        event(new BatchCraftingMonitoringUpdated($character->id));

        if (! app()->runningUnitTests()) {
            $pendingDispatch = BatchCraftingJob::dispatch($batchCrafting->id);

            if ($requiresInitialFiniteDelay || ! $this->isContinuousFiniteType($type, $progress)) {
                $pendingDispatch->delay($firstAttemptAt);
            }
        }

        return $batchCrafting;
    }

    public function isContinuousFiniteMode(BatchCrafting $batchCrafting): bool
    {
        return $batchCrafting->isRunning()
            && $this->isContinuousFiniteType(BatchCraftingType::from($batchCrafting->batch_type), $batchCrafting->progress ?? []);
    }

    private function isContinuousFiniteType(BatchCraftingType $type, array $progress): bool
    {
        return match ($type) {
            BatchCraftingType::CRAFT => in_array($progress['craft_mode'] ?? null, ['specific_item', 'craft_set'], true),
            BatchCraftingType::CRAFT_AND_ENCHANT => in_array($progress['craft_mode'] ?? null, ['specific_item', 'craft_enchant_set'], true),
            BatchCraftingType::ALCHEMY => ($progress['alchemy_mode'] ?? null) === 'amount',
            BatchCraftingType::HOLY_OILS => in_array($progress['holy_oil_mode'] ?? 'selected', ['selected', 'set'], true),
            default => false,
        };
    }

    private function requiresInitialFiniteDelay(BatchCraftingType $type, array $progress): bool
    {
        return match ($type) {
            BatchCraftingType::CRAFT => in_array($progress['craft_mode'] ?? null, ['specific_item', 'craft_set'], true),
            BatchCraftingType::CRAFT_AND_ENCHANT => in_array($progress['craft_mode'] ?? null, ['specific_item', 'craft_enchant_set'], true),
            BatchCraftingType::ALCHEMY => ($progress['alchemy_mode'] ?? null) === 'amount',
            BatchCraftingType::HOLY_OILS => in_array($progress['holy_oil_mode'] ?? 'selected', ['selected', 'set'], true),
            default => false,
        };
    }

    public function preview(Character $character, array $data): array
    {
        $type = BatchCraftingType::from($data['batch_type']);
        $progress = $data['progress'] ?? [];
        $selectedItemIds = $data['selected_items'] ?? [];
        $selectedOilIds = $data['selected_oils'] ?? [];
        $disposition = isset($data['disposition']) ? BatchCraftingDisposition::from($data['disposition']) : BatchCraftingDisposition::KEEP;
        $progress = $this->normalizeOutputDestination($type, $progress, $disposition);

        return [
            'cost_breakdown' => $this->costBreakdown($character, $type, $progress, $selectedItemIds, $selectedOilIds, $disposition),
            'amount_preview' => $this->amountPreview($character, $type, $progress, $disposition),
            'alchemy_amount_preview' => $this->alchemyAmountPreview($character, $type, $progress, $disposition),
            'holy_oil_selected_preview' => $this->holyOilsSelectedPreview($character, $type, $selectedItemIds, $selectedOilIds, $progress),
            'holy_oil_set_preview' => $this->holyOilsSetPreview($character, $type, $selectedOilIds, $progress),
            'destination_capacity' => $this->destinationCapacityPreview($character, $type, $progress, $disposition),
            'maximum_request_amount' => $this->maximumRequestAmount($character, $type, $progress, $disposition),
            'start_blockers' => $this->startBlockers($character, $type, $progress, $selectedItemIds, $selectedOilIds, $disposition, true),
        ];
    }

    /**
     * Destination capacity can refer to Inventory, a specified normal Inventory Set,
     * the Crafted Items Set, or the Alchemy Bag for Alchemy modes.
     */
    private function destinationCapacityPreview(Character $character, BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): ?array
    {
        if ($this->requiresCraftedItemsSetCapacity($type, $progress, $disposition)) {
            $batchCraftingSet = $this->batchCraftingSetService->getOrCreateForCharacter($character);

            return [
                'destination' => 'crafted_items_set',
                'destination_label' => 'Crafted Items Set',
                'current' => $batchCraftingSet->currentSlotCount(),
                'max' => $batchCraftingSet->max_slots,
                'remaining' => $batchCraftingSet->remainingSlots(),
            ];
        }

        if ($this->requiresAlchemyBagCapacity($type, $progress, $disposition)) {
            return [
                'destination' => 'alchemy_bag',
                'destination_label' => 'Alchemy Bag',
                'current' => $character->getAlchemyBagCount(),
                'max' => $character->alchemy_bag_limit,
                'remaining' => max(0, $character->alchemy_bag_limit - $character->getAlchemyBagCount()),
            ];
        }

        if ($this->requiresInventoryCapacity($type, $progress, $disposition)) {
            return [
                'destination' => 'inventory',
                'destination_label' => 'Inventory',
                'current' => $character->getInventoryCount(),
                'max' => $character->inventory_max,
                'remaining' => max(0, $character->inventory_max - $character->getInventoryCount()),
            ];
        }

        if ($this->requiresInventorySetCapacity($type, $progress, $disposition)) {
            $outputSet = InventorySet::where('id', $progress['output_set_id'] ?? 0)->where('character_id', $character->id)->first();

            if (is_null($outputSet)) {
                return null;
            }

            return [
                'destination' => 'inventory_set',
                'destination_label' => $this->inventorySetDisplayName($character, $outputSet),
                'current' => $outputSet->currentSlotCount(),
                'max' => $outputSet->max_slots,
                'remaining' => $outputSet->remainingSlots(),
            ];
        }

        return null;
    }

    private function maximumRequestAmount(Character $character, BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): int
    {
        if (! in_array($disposition, [BatchCraftingDisposition::KEEP], true)) {
            return 2000;
        }

        $capacity = $this->destinationCapacityPreview($character, $type, $progress, $disposition);

        if (is_null($capacity)) {
            return 2000;
        }

        return min(2000, max(0, (int) $capacity['remaining']));
    }

    private function startBlockers(Character $character, BatchCraftingType $type, array $progress, array $selectedItemIds, array $selectedOilIds, BatchCraftingDisposition $disposition, bool $includePreviewPlanBlockers = false): array
    {
        $craftMode = $progress['craft_mode'] ?? null;

        if ($type === BatchCraftingType::CRAFT && $craftMode === 'specific_item') {
            return $this->amountModeStartBlockers($character, $type, $progress, $this->amountPreview($character, $type, $progress, $disposition), $disposition);
        }

        if ($type === BatchCraftingType::CRAFT && $craftMode === 'craft_set') {
            return $this->craftSetStartBlockers($character, $progress, false, $disposition, $includePreviewPlanBlockers);
        }

        if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && $craftMode === 'specific_item') {
            return array_merge(
                $this->manualSelectedAffixIntBlockers($character, $progress['enchant_affix_ids'] ?? []),
                $this->amountModeStartBlockers($character, $type, $progress, $this->amountPreview($character, $type, $progress, $disposition), $disposition)
            );
        }

        if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && $craftMode === 'craft_enchant_set') {
            return $this->craftSetStartBlockers($character, $progress, true, $disposition, $includePreviewPlanBlockers);
        }

        if ($type === BatchCraftingType::ALCHEMY && ($progress['alchemy_mode'] ?? null) === 'amount') {
            return $this->alchemyAmountStartBlockers($character, $progress, $disposition);
        }

        if (in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true) && $craftMode === 'experience') {
            if (! $this->requiresCraftedItemsSetCapacity($type, $progress, $disposition)) {
                return [];
            }

            return $this->craftedItemsSetCapacityBlockers($character, $this->retainedCraftedItemsSetSlots($type, $progress, $disposition));
        }

        if ($type === BatchCraftingType::TRINKETRY) {
            $blockers = $this->trinketryStartBlockers($character);

            if (! $this->requiresCraftedItemsSetCapacity($type, $progress, $disposition)) {
                return $blockers;
            }

            return array_merge(
                $blockers,
                $this->craftedItemsSetCapacityBlockers($character, $this->retainedCraftedItemsSetSlots($type, $progress, $disposition)),
            );
        }

        if ($type === BatchCraftingType::ALCHEMY && ($progress['alchemy_mode'] ?? null) === 'experience') {
            if (! $this->requiresAlchemyBagCapacity($type, $progress, $disposition)) {
                return [];
            }

            return $this->alchemyBagCapacityBlockers($character, $this->retainedAlchemyBagSlots($disposition));
        }

        return [];
    }

    private function trinketryStartBlockers(Character $character): array
    {
        $cost = $this->processor->trinketryCostPreview($character);

        if (is_null($cost)) {
            return [[
                'code' => 'trinketry_item_unavailable',
                'message' => 'No XP-eligible trinket is currently available.',
                'blocking' => true,
            ]];
        }

        $missingCurrencies = [];

        foreach (['gold_dust' => 'Gold Dust', 'copper_coins' => 'Copper Coins'] as $currency => $label) {
            if ($cost[$currency]['missing'] > 0) {
                $missingCurrencies[] = $label
                    .' required '.number_format($cost[$currency]['required'])
                    .', available '.number_format($cost[$currency]['available'])
                    .', missing '.number_format($cost[$currency]['missing']);
            }
        }

        if (empty($missingCurrencies)) {
            return [];
        }

        return [[
            'code' => 'trinketry_insufficient_currencies',
            'message' => 'Batch Trinketry cannot start crafting '.$cost['item_name'].'. Missing: '.implode('; ', $missingCurrencies).'.',
            'blocking' => true,
        ]];
    }

    /**
     * Kept output for Craft/Craft and Enchant and Trinketry is created in the
     * Crafted Items Set, not normal inventory.
     */
    private function craftedItemsSetCapacityBlockers(Character $character, int $requiredSlots = 1): array
    {
        $batchCraftingSet = $this->batchCraftingSetService->getOrCreateForCharacter($character);
        $remaining = $batchCraftingSet->remainingSlots();

        if ($remaining <= 0) {
            return [$this->blocker(
                'crafted_items_set_full',
                'Your Crafted Items Set is full. Empty space before starting this batch.'
            )];
        }

        if ($remaining < $requiredSlots) {
            return [$this->blocker(
                'crafted_items_set_not_enough_space',
                'Your Crafted Items Set does not have enough space. Needed: '.number_format($requiredSlots).', Remaining space: '.number_format($remaining).'.'
            )];
        }

        return [];
    }

    /**
     * Kept output destined for normal Inventory (output_destination === 'inventory').
     */
    private function inventoryCapacityBlockers(Character $character, int $requiredSlots = 1): array
    {
        $remaining = max(0, $character->inventory_max - $character->getInventoryCount());

        if ($remaining <= 0) {
            return [$this->blocker(
                'inventory_full',
                'Your Inventory is full. Empty space before starting this batch.'
            )];
        }

        if ($remaining < $requiredSlots) {
            return [$this->blocker(
                'inventory_not_enough_space',
                'Your Inventory does not have enough space. Needed: '.number_format($requiredSlots).', Remaining space: '.number_format($remaining).'.'
            )];
        }

        return [];
    }

    /**
     * Output for Alchemy Experience mode is created in the Alchemy Bag, not normal
     * inventory.
     */
    private function alchemyBagCapacityBlockers(Character $character, int $requiredSlots = 1): array
    {
        $remaining = max(0, $character->alchemy_bag_limit - $character->getAlchemyBagCount());

        if ($remaining <= 0) {
            return [$this->blocker(
                'alchemy_bag_full',
                'Your Alchemy Bag is full. Empty space before starting this batch.'
            )];
        }

        if ($remaining < $requiredSlots) {
            return [$this->blocker(
                'alchemy_bag_not_enough_space',
                'Your Alchemy Bag does not have enough space. Needed: '.number_format($requiredSlots).', Remaining space: '.number_format($remaining).'.'
            )];
        }

        return [];
    }

    private function blocker(string $code, string $message, array $links = [], bool $blocking = true, array $metadata = []): array
    {
        return array_merge([
            'code' => $code,
            'message' => $message,
            'blocking' => $blocking,
            'links' => $links,
        ], $metadata);
    }

    private function intGuidanceLinks(): array
    {
        return array_merge([
            ['url' => '/information/enchanting?table-filters[types]=0', 'label' => 'Stat based enchants'],
            ['url' => '/information/enchanting?table-filters[types]=15', 'label' => 'Spell crafting - raises INT'],
            ['url' => '/information/enchanting?table-filters[types]=16', 'label' => 'Enchantment crafting - raises INT'],
        ], $this->craftingIntItemLinks());
    }

    private function craftingIntItemLinks(): array
    {
        return [
            ['url' => '/information/crafting?filter=wand', 'label' => 'Wands'],
            ['url' => '/information/crafting?filter=stave', 'label' => 'Staves'],
            ['url' => '/information/crafting?filter=spell-damage', 'label' => 'Spell Damage'],
        ];
    }

    private function manualSelectedAffixIntBlockers(Character $character, array $affixIds): array
    {
        $affixIds = array_values(array_filter($affixIds, fn ($affixId) => ! is_null($affixId)));

        if (empty($affixIds)) {
            return [];
        }

        $characterInt = $character->getInformation()->statMod('int');
        $affixes = ItemAffix::whereIn('id', $affixIds)->get();
        $blockers = [];

        foreach ($affixes as $affix) {
            if ($characterInt >= $affix->int_required) {
                continue;
            }

            $blockers[] = $this->blocker(
                'int_too_low_for_enchanting',
                $affix->name.' requires '.number_format($affix->int_required).' INT. You have '.number_format($characterInt).' INT.',
                $this->intGuidanceLinks(),
                true,
                [
                    'affix_id' => $affix->id,
                    'affix_name' => $affix->name,
                    'affix_type' => $affix->type,
                    'int_required' => $affix->int_required,
                    'character_int' => $characterInt,
                ]
            );
        }

        return $blockers;
    }

    private function amountModeStartBlockers(Character $character, BatchCraftingType $type, array $progress, ?array $preview, BatchCraftingDisposition $disposition): array
    {
        if (is_null($preview)) {
            return [];
        }

        $blockers = [];

        if ($preview['total_cost'] > $preview['available_gold']) {
            $missing = $preview['total_cost'] - $preview['available_gold'];

            $blockers[] = $this->blocker(
                'not_enough_gold',
                'You do not have enough Gold to start this batch. Required: '.number_format($preview['total_cost']).', Available: '.number_format($preview['available_gold']).', Missing: '.number_format($missing).'.'
            );
        }

        if (! $this->keepsOutputInCraftedItemsSet($disposition)) {
            return $blockers;
        }

        return array_merge($blockers, $this->outputDestinationStartBlockers($character, $type, $progress, $disposition, max(1, $preview['remaining_requested_amount'])));
    }

    /**
     * Authoritative start-time capacity/validity blockers for the resolved output
     * destination of a finite retained-output batch (Inventory / Specified Empty Set /
     * Crafted Items Set). Reuses targetSetStartBlockers() for a specified set so
     * ownership, equipped, empty and capacity rules are never duplicated.
     */
    private function outputDestinationStartBlockers(Character $character, BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition, int $requiredSlots): array
    {
        if ($this->requiresInventorySetCapacity($type, $progress, $disposition)) {
            return $this->targetSetStartBlockers($character, (int) ($progress['output_set_id'] ?? 0), $requiredSlots, false);
        }

        if ($this->requiresInventoryCapacity($type, $progress, $disposition)) {
            return $this->inventoryCapacityBlockers($character, $requiredSlots);
        }

        if ($this->requiresCraftedItemsSetCapacity($type, $progress, $disposition)) {
            return $this->craftedItemsSetCapacityBlockers($character, $requiredSlots);
        }

        return [];
    }

    private function alchemyAmountStartBlockers(Character $character, array $progress, BatchCraftingDisposition $disposition): array
    {
        $preview = $this->alchemyAmountPreview($character, BatchCraftingType::ALCHEMY, $progress, $disposition);

        if (is_null($preview)) {
            return [];
        }

        $blockers = [];

        if ($preview['total_gold_dust_cost'] > $preview['available_gold_dust']) {
            $missing = $preview['total_gold_dust_cost'] - $preview['available_gold_dust'];

            $blockers[] = $this->blocker(
                'not_enough_gold_dust',
                'You do not have enough Gold Dust to start this batch. Required: '.number_format($preview['total_gold_dust_cost']).', Available: '.number_format($preview['available_gold_dust']).', Missing: '.number_format($missing).'.'
            );
        }

        if ($preview['total_shards_cost'] > $preview['available_shards']) {
            $missing = $preview['total_shards_cost'] - $preview['available_shards'];

            $blockers[] = $this->blocker(
                'not_enough_shards',
                'You do not have enough Shards to start this batch. Required: '.number_format($preview['total_shards_cost']).', Available: '.number_format($preview['available_shards']).', Missing: '.number_format($missing).'.'
            );
        }

        if (! $this->keepsOutputInAlchemyBag($disposition)) {
            return $blockers;
        }

        if ($preview['bag_remaining'] <= 0) {
            $blockers[] = $this->blocker(
                'alchemy_bag_full',
                'Your Alchemy Bag is full. Empty space before starting this batch.'
            );

            return $blockers;
        }

        if ($preview['remaining_requested_amount'] > $preview['bag_remaining']) {
            $blockers[] = $this->blocker(
                'alchemy_bag_not_enough_space',
                'Your Alchemy Bag does not have enough space. Requested: '.number_format($preview['remaining_requested_amount']).', Remaining space: '.number_format($preview['bag_remaining']).'.'
            );
        }

        return $blockers;
    }

    private function craftSetStartBlockers(Character $character, array $progress, bool $includeEnchanting, BatchCraftingDisposition $disposition, bool $includePreviewPlanBlockers): array
    {
        $planKey = $includeEnchanting ? 'enchant_plan' : 'craft_set_plan';
        $plan = is_array($progress[$planKey] ?? null) ? $progress[$planKey] : [];
        $breakdown = $this->craftSetCostBreakdown($character, [], $includeEnchanting, $plan, false, $progress);
        $blockers = $includeEnchanting ? $this->craftEnchantSetPlanIntBlockers($character, $plan) : [];

        foreach ($includePreviewPlanBlockers ? ($breakdown['plan_entries'] ?? []) : [] as $entry) {
            $targetLabel = (string) ($entry['label'] ?? ucwords(str_replace('_', ' ', (string) ($entry['target']['type'] ?? $entry['key']))));
            $requestedSelectedItemId = $entry['requested_selected_item_id'] ?? null;

            if (! is_null($requestedSelectedItemId) && ! ($entry['selected_item_available'] ?? false)) {
                $blockers[] = $this->blocker(
                    'craft_plan_selected_item_unavailable',
                    'The selected item for '.$targetLabel.' is no longer craftable. Select another item before starting.',
                    [],
                    true,
                    ['plan_key' => $entry['key'], 'selected_item_id' => $requestedSelectedItemId, 'target_label' => $targetLabel]
                );

                continue;
            }

            if (empty($entry['available_items']) && ! ($entry['optional'] ?? false)) {
                $blockers[] = $this->blocker(
                    'craft_plan_no_craftable_item',
                    'No craftable item is currently available for '.$targetLabel.'.',
                    [],
                    true,
                    ['plan_key' => $entry['key'], 'target_label' => $targetLabel]
                );
            }
        }

        $selectedHands = collect($breakdown['plan_entries'] ?? [])
            ->filter(fn (array $entry) => $entry['category'] === 'hand' && $entry['included'])
            ->map(fn (array $entry) => Item::find($entry['selected_item_id']))
            ->filter();

        if (! $this->setHandsValidation->areHandItemsValid($selectedHands)) {
            $blockers[] = $this->blocker(
                'craft_set_invalid_hand_combination',
                'A two-handed weapon occupies both hands. Clear the other hand before starting.',
                [],
                true
            );
        }

        if (! ($breakdown['can_afford_full_plan'] ?? true)) {
            $blockers[] = $this->blocker(
                'not_enough_gold',
                'You do not have enough Gold to start this batch. Required: '.number_format((int) $breakdown['total_required_gold']).', Available: '.number_format((int) $character->gold).', Missing: '.number_format((int) $breakdown['missing_currency_amount']).'.'
            );
        }

        if (! $this->keepsOutputInCraftedItemsSet($disposition)) {
            return $blockers;
        }

        $type = $includeEnchanting ? BatchCraftingType::CRAFT_AND_ENCHANT : BatchCraftingType::CRAFT;

        return array_merge($blockers, $this->outputDestinationStartBlockers($character, $type, $progress, $disposition, (int) ($breakdown['planned_items'] ?? 0)));
    }

    /**
     * Mirrors BatchCraftingProcessor::shouldMoveKeptOutputToBatchSet() for start-time
     * validation: only dispositions that actually place output in the Crafted Items Set
     * should require capacity there before a batch is allowed to start.
     */
    private function keepsOutputInCraftedItemsSet(BatchCraftingDisposition $disposition): bool
    {
        return in_array($disposition, [
            BatchCraftingDisposition::KEEP,
            BatchCraftingDisposition::KEEP_HIGHEST,
            BatchCraftingDisposition::KEEP_BEST_SELL_REST,
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST,
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
        ], true);
    }

    private function keepsOutputInAlchemyBag(BatchCraftingDisposition $disposition): bool
    {
        return in_array($disposition, [
            BatchCraftingDisposition::KEEP,
            BatchCraftingDisposition::KEEP_HIGHEST,
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
        ], true);
    }

    /**
     * The four finite KEEP modes that retain every final item and offer the three-way
     * output destination selector (Inventory / Specified Empty Set / Crafted Items Set).
     * Experience mode and every other mode keep their existing, non-selectable
     * Crafted Items Set destination.
     */
    private function isSelectableOutputDestinationMode(BatchCraftingType $type, array $progress): bool
    {
        $craftMode = $progress['craft_mode'] ?? null;

        return ($type === BatchCraftingType::CRAFT && in_array($craftMode, ['specific_item', 'craft_set'], true))
            || ($type === BatchCraftingType::CRAFT_AND_ENCHANT && in_array($craftMode, ['specific_item', 'craft_enchant_set'], true));
    }

    private function requiresCraftedItemsSetCapacity(BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): bool
    {
        if (! $this->keepsOutputInCraftedItemsSet($disposition)) {
            return false;
        }

        $craftMode = $progress['craft_mode'] ?? null;

        if ($type === BatchCraftingType::TRINKETRY) {
            return true;
        }

        if (in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true) && $craftMode === 'experience') {
            return true;
        }

        if (! $this->isSelectableOutputDestinationMode($type, $progress)) {
            return false;
        }

        return $this->resolvedOutputDestination($progress) === 'crafted_items_set';
    }

    private function requiresInventoryCapacity(BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): bool
    {
        if (! $this->keepsOutputInCraftedItemsSet($disposition)) {
            return false;
        }

        return $this->isSelectableOutputDestinationMode($type, $progress)
            && $this->resolvedOutputDestination($progress) === 'inventory';
    }

    private function requiresInventorySetCapacity(BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): bool
    {
        if (! $this->keepsOutputInCraftedItemsSet($disposition)) {
            return false;
        }

        return $this->isSelectableOutputDestinationMode($type, $progress)
            && $this->resolvedOutputDestination($progress) === 'inventory_set';
    }

    private function requiresAlchemyBagCapacity(BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): bool
    {
        return $type === BatchCraftingType::ALCHEMY
            && in_array(($progress['alchemy_mode'] ?? null), ['amount', 'experience'], true)
            && $this->keepsOutputInAlchemyBag($disposition);
    }

    private function retainedCraftedItemsSetSlots(BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): int
    {
        $craftMode = $progress['craft_mode'] ?? null;

        if ($craftMode === 'specific_item') {
            return max(1, (int) ($progress['craft_amount'] ?? 1) - (int) ($progress['craft_specific_count'] ?? $progress['craft_enchant_specific_count'] ?? 0));
        }

        if (in_array($craftMode, ['craft_set', 'craft_enchant_set'], true)) {
            return count($progress['craft_set_queue'] ?? $progress['craft_enchant_set_queue'] ?? []);
        }

        if ($disposition === BatchCraftingDisposition::KEEP) {
            return $type === BatchCraftingType::TRINKETRY
                ? self::ITEMS_PER_RECURRING_TICK
                : self::ITEMS_PER_FULL_SET;
        }

        if ($type === BatchCraftingType::TRINKETRY) {
            return 1;
        }

        return self::ITEMS_PER_FULL_SET - 1;
    }

    private function retainedAlchemyBagSlots(BatchCraftingDisposition $disposition): int
    {
        return $disposition === BatchCraftingDisposition::KEEP ? self::ITEMS_PER_RECURRING_TICK : 1;
    }

    private function craftEnchantSetPlanIntBlockers(Character $character, array $plan): array
    {
        $affixLookups = [];

        foreach ($plan as $planKey => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach (['prefix_affix_id' => 'prefix', 'suffix_affix_id' => 'suffix'] as $field => $affixType) {
                $affixId = $entry[$field] ?? null;

                if (! is_null($affixId)) {
                    $affixLookups[] = ['plan_key' => $planKey, 'affix_type' => $affixType, 'affix_id' => (int) $affixId];
                }
            }
        }

        if (empty($affixLookups)) {
            return [];
        }

        $characterInt = $character->getInformation()->statMod('int');
        $affixes = ItemAffix::whereIn('id', array_column($affixLookups, 'affix_id'))->get()->keyBy('id');
        $blockers = [];

        foreach ($affixLookups as $lookup) {
            $affix = $affixes->get($lookup['affix_id']);

            if (is_null($affix) || $characterInt >= $affix->int_required) {
                continue;
            }

            $blockers[] = $this->blocker(
                'int_too_low_for_enchanting',
                $affix->name.' requires '.number_format($affix->int_required).' INT. You have '.number_format($characterInt).' INT.',
                $this->intGuidanceLinks(),
                true,
                [
                    'plan_key' => $lookup['plan_key'],
                    'affix_id' => $affix->id,
                    'affix_name' => $affix->name,
                    'affix_type' => $lookup['affix_type'],
                    'int_required' => $affix->int_required,
                    'character_int' => $characterInt,
                ]
            );
        }

        return $blockers;
    }

    private function targetSetStartBlockers(Character $character, int $setId, int $requiredSlots, bool $allowValidFullSet = false): array
    {
        $set = InventorySet::where('id', $setId)->where('character_id', $character->id)->first();

        if (is_null($set)) {
            return [$this->blocker(
                'target_set_invalid',
                'The selected set does not belong to this character.'
            )];
        }

        if ($set->isBatchCraftingSet()) {
            return [$this->blocker(
                'target_set_is_batch_crafting_set',
                'The Crafted Items Set cannot be used as a target set. Choose a different set.'
            )];
        }

        if ($set->is_equipped) {
            return [$this->blocker(
                'target_set_equipped',
                'Equipped sets cannot be used for this batch. Unequip the set or choose another set.'
            )];
        }

        $currentSlots = $set->slots()->count();

        if ($currentSlots === 0) {
            if (! is_null($set->max_slots) && $set->max_slots < $requiredSlots) {
                return [$this->blocker(
                    'target_set_full',
                    'The selected set does not have enough space for this batch. Needed: '.number_format($requiredSlots).', Max slots: '.number_format($set->max_slots).'.'
                )];
            }

            return [];
        }

        if (! $allowValidFullSet) {
            return [$this->blocker(
                'target_set_not_empty',
                'The selected set must be empty before starting this batch.'
            )];
        }

        $composition = $this->validateEnchantableSetComposition($set);

        if (! $composition['valid']) {
            return [$this->blocker('target_set_invalid_composition', $composition['message'])];
        }

        if ($composition['has_enchants']) {
            return [$this->blocker(
                'target_set_already_enchanted',
                'This valid set has enchants on it. They will be overwritten or the item may be destroyed if your enchanting skill is not high enough. You may proceed, but be cautious. Uniques, Mythics and Cosmic items will not be touched.',
                [],
                false
            )];
        }

        return [];
    }

    private function validateEnchantableSetComposition(InventorySet $set): array
    {
        $invalidResult = ['valid' => false, 'has_enchants' => false, 'message' => $this->invalidSetCompositionMessage()];
        $items = $set->slots()->with('item')->get()->map(fn (SetSlot $slot) => $slot->item)->filter();

        if ($items->count() !== 23) {
            return $invalidResult;
        }

        if ($items->contains(fn (Item $item) => $item->is_unique || $item->is_mythic || $item->is_cosmic)) {
            return $invalidResult;
        }

        $typeCounts = $items->countBy(fn (Item $item) => $item->type);

        foreach (ItemType::validWeapons() as $weaponType) {
            if (($typeCounts[$weaponType] ?? 0) !== 1) {
                return $invalidResult;
            }
        }

        foreach (ArmourType::allTypes() as $armourType) {
            if (($typeCounts[$armourType] ?? 0) !== 1) {
                return $invalidResult;
            }
        }

        if (($typeCounts[ItemType::RING->value] ?? 0) !== 2) {
            return $invalidResult;
        }

        if (($typeCounts[ItemType::SPELL_DAMAGE->value] ?? 0) !== 1 || ($typeCounts[ItemType::SPELL_HEALING->value] ?? 0) !== 1) {
            return $invalidResult;
        }

        $hasEnchants = $items->contains(fn (Item $item) => ! is_null($item->item_prefix_id) || ! is_null($item->item_suffix_id));

        return ['valid' => true, 'has_enchants' => $hasEnchants, 'message' => null];
    }

    private function invalidSetCompositionMessage(): string
    {
        return 'Your set is not a valid set to enchant all the items. You must have 23 items in the set, which consist of: 12 weapons (daggers, swords, claws, wands, censers, bows, staves, hammers, maces, scratch awls, guns, fans), 7 armour pieces (shield, body, leggings, sleeves, gloves, feet, helmet), 2 rings, and 2 spells (spell damage, spell healing). You can create one by crafting a set or selecting an empty set to craft and enchant the items.';
    }

    private function costBreakdown(Character $character, BatchCraftingType $type, array $progress, array $selectedItemIds, array $selectedOilIds, BatchCraftingDisposition $disposition): array
    {
        if ($type === BatchCraftingType::TRINKETRY) {
            $cost = $this->processor->trinketryCostPreview($character);

            if (is_null($cost)) {
                return [
                    'currency' => 'trinketry_currencies',
                    'currency_label' => 'Gold Dust and Copper Coins',
                    'required_to_start' => 0,
                    'available_currency_amount' => null,
                    'can_afford_start' => false,
                    'total_cost_known' => false,
                    'total_required' => null,
                    'effective_amount' => 0,
                    'destination' => null,
                    'source_hint' => null,
                    'message' => 'No XP-eligible trinket is currently available.',
                ];
            }

            return [
                'currency' => 'trinketry_currencies',
                'currency_label' => 'Gold Dust and Copper Coins',
                'required_to_start' => null,
                'available_currency_amount' => null,
                'can_afford_start' => $cost['gold_dust']['missing'] === 0
                    && $cost['copper_coins']['missing'] === 0,
                'total_cost_known' => true,
                'total_required' => null,
                'effective_amount' => 1,
                'destination' => null,
                'source_hint' => null,
                'message' => null,
                'trinket' => ['id' => $cost['item_id'], 'name' => $cost['item_name']],
                'gold_dust' => $cost['gold_dust'],
                'copper_coins' => $cost['copper_coins'],
            ];
        }

        $currency = $type->requiredCurrency();
        $available = $this->currencyAmount($character, $currency);
        $breakdown = [
            'currency' => $currency,
            'currency_label' => $this->currencyLabel($currency),
            'required_to_start' => 1,
            'available_currency_amount' => $available,
            'can_afford_start' => $available >= 1,
            'total_cost_known' => false,
            'total_required' => null,
            'effective_amount' => null,
            'destination' => null,
            'source_hint' => $this->currencySourceHint($currency),
            'message' => null,
        ];

        $amountPreview = $this->amountPreview($character, $type, $progress, $disposition);

        if (! is_null($amountPreview)) {
            $breakdown['required_to_start'] = (int) $amountPreview['total_per_item_cost'];
            $breakdown['total_cost_known'] = true;
            $breakdown['total_required'] = (int) $amountPreview['total_cost'];
            $breakdown['effective_amount'] = (int) $amountPreview['effective_craftable_amount'];
            $breakdown['destination'] = $this->keepsOutputInCraftedItemsSet($disposition) ? 'Crafted Items Set' : null;
            $breakdown['can_afford_start'] = $amountPreview['effective_craftable_amount'] > 0;

            return $breakdown;
        }

        $alchemyPreview = $this->alchemyAmountPreview($character, $type, $progress, $disposition);

        if (! is_null($alchemyPreview)) {
            $requiredToStart = (int) $alchemyPreview['gold_dust_cost_per_item'] + (int) $alchemyPreview['shards_cost_per_item'];
            $breakdown['required_to_start'] = $requiredToStart;
            $breakdown['total_cost_known'] = true;
            $breakdown['total_required_gold_dust'] = (int) $alchemyPreview['total_gold_dust_cost'];
            $breakdown['total_required_shards'] = (int) $alchemyPreview['total_shards_cost'];
            $breakdown['available_gold_dust'] = (int) $alchemyPreview['available_gold_dust'];
            $breakdown['available_shards'] = (int) $alchemyPreview['available_shards'];
            $breakdown['effective_amount'] = (int) $alchemyPreview['effective_craftable_amount'];
            $breakdown['destination'] = $this->keepsOutputInAlchemyBag($disposition) ? 'Alchemy Bag' : null;
            $breakdown['can_afford_start'] = $alchemyPreview['effective_craftable_amount'] > 0;

            return $breakdown;
        }

        if ($type === BatchCraftingType::CRAFT && ($progress['craft_mode'] ?? null) === 'craft_set') {
            return $this->craftSetCostBreakdown($character, $breakdown, false, is_array($progress['craft_set_plan'] ?? null) ? $progress['craft_set_plan'] : [], false, $progress);
        }

        if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && ($progress['craft_mode'] ?? null) === 'craft_enchant_set') {
            return $this->craftSetCostBreakdown($character, $breakdown, true, is_array($progress['enchant_plan'] ?? null) ? $progress['enchant_plan'] : [], false, $progress);
        }

        $holyOilSelectedPreview = $this->holyOilsSelectedPreview($character, $type, $selectedItemIds, $selectedOilIds, $progress);
        $holyOilSetPreview = $this->holyOilsSetPreview($character, $type, $selectedOilIds, $progress);
        $holyOilPreview = $holyOilSelectedPreview ?? $holyOilSetPreview;

        if (! is_null($holyOilPreview)) {
            $breakdown['required_to_start'] = (int) ($holyOilPreview['application_sequence'][0]['gold_dust_cost'] ?? 0);
            $breakdown['total_cost_known'] = true;
            $breakdown['total_required'] = (int) $holyOilPreview['exact_gold_dust_required'];
            $breakdown['available_currency_amount'] = (int) $holyOilPreview['gold_dust_available'];
            $breakdown['effective_amount'] = (int) $holyOilPreview['applications_planned'];
            $breakdown['destination'] = $holyOilSetPreview ? 'Inventory Set' : 'Selected Gear';
            $breakdown['can_afford_start'] = $holyOilPreview['applications_planned'] > 0;

            return $breakdown;
        }

        if ($type === BatchCraftingType::ALCHEMY) {
            $breakdown['message'] = $this->missingRequiredCurrencyMessage($character, $type);
        }

        return $breakdown;
    }

    private function craftSetCostBreakdown(Character $character, array $breakdown, bool $includeEnchanting, array $plan, bool $isEnchantExisting = false, array $progress = []): array
    {
        $selectedItemIds = $this->selectedItemIdsFromPlan($plan);
        $previewItems = $this->processor->craftSetPreviewItems($character, $selectedItemIds);
        $craftCost = 0;
        $enchantCost = 0;
        $configuredItems = 0;
        $planEntries = [];

        $defaultPrefix = $includeEnchanting ? $this->highestValidAffix($character, 'prefix') : null;
        $defaultSuffix = $includeEnchanting ? $this->highestValidAffix($character, 'suffix') : null;

        foreach ($previewItems as $entry) {
            $item = $entry['item'];
            $included = (bool) $entry['included'];

            if ($included && ! is_null($item)) {
                $craftCost += (int) $item->cost;
            }

            $planEntry = is_array($plan[$entry['key']] ?? null) ? $plan[$entry['key']] : [];
            $prefixId = $includeEnchanting ? ($planEntry['prefix_affix_id'] ?? null) : null;
            $suffixId = $includeEnchanting ? ($planEntry['suffix_affix_id'] ?? null) : null;
            $prefixCost = 0;
            $suffixCost = 0;

            if ($includeEnchanting && $included && ! is_null($item)) {
                if (! is_null($prefixId)) {
                    $prefixCost = $this->enchantingService->getCostOfEnchantment($character, [$prefixId], $item->id);
                }

                if (! is_null($suffixId)) {
                    $suffixCost = $this->enchantingService->getCostOfEnchantment($character, [$suffixId], $item->id);
                }

                if (! is_null($prefixId) || ! is_null($suffixId)) {
                    $configuredItems++;
                    $enchantCost += $prefixCost + $suffixCost;
                }
            }

            $planEntries[] = [
                'key' => $entry['key'],
                'label' => $entry['target']['label'],
                'category' => $entry['target']['category'],
                'optional' => $entry['target']['optional'],
                'included' => $included,
                'target' => $entry['target'],
                'requested_selected_item_id' => $entry['requested_selected_item_id'],
                'selected_item_available' => $entry['selected_item_available'],
                'selected_item_id' => $item?->id,
                'selected_item_name' => $item?->affix_name ?? $item?->name,
                'selected_item_cost' => (int) ($item?->cost ?? 0),
                'selected_item_details' => is_null($item) ? null : $this->itemTransformer->transform($item),
                'selected_item_type' => $item?->type,
                'selected_item_handedness' => is_null($item) ? null : $this->setHandsValidation->handedness($item),
                'prefix_cost' => $prefixCost,
                'suffix_cost' => $suffixCost,
                'available_items' => $entry['available_items'],
            ];
        }

        $totalRequired = $craftCost + $enchantCost;

        $breakdown['total_cost_known'] = true;
        $breakdown['destination'] = $isEnchantExisting ? 'Selected Inventory Set' : $this->outputDestinationLabel($character, $progress);
        $plannedItems = collect($previewItems)->where('included', true)->count();
        $breakdown['planned_items'] = $plannedItems;
        $breakdown['configured_items'] = $includeEnchanting ? $configuredItems : $plannedItems;
        $breakdown['craft_cost_total'] = $craftCost;
        $breakdown['enchant_cost_total'] = $includeEnchanting ? $enchantCost : 0;
        $breakdown['total_required_gold'] = $totalRequired;
        $breakdown['total_required'] = $totalRequired;
        $breakdown['required_to_start'] = $totalRequired;
        $breakdown['missing_currency_amount'] = max(0, $totalRequired - (int) $character->gold);
        $breakdown['can_afford_full_plan'] = (int) $character->gold >= $totalRequired;
        $breakdown['can_afford_start'] = (int) $character->gold >= $totalRequired && $totalRequired > 0;
        $breakdown['effective_amount'] = $breakdown['can_afford_start'] ? $plannedItems : 0;
        $breakdown['enchant_has_failure_risk'] = $includeEnchanting && $this->enchantingSkillLevel($character) < 400;
        $breakdown['default_prefix_affix_id'] = $defaultPrefix?->id;
        $breakdown['default_prefix_affix_name'] = $defaultPrefix?->name;
        $breakdown['default_suffix_affix_id'] = $defaultSuffix?->id;
        $breakdown['default_suffix_affix_name'] = $defaultSuffix?->name;
        $breakdown['plan_entries'] = $planEntries;

        return $breakdown;
    }

    /**
     * Highest valid affix of the given type for this character: enchanting skill and
     * INT requirements met, ranked by skill_level_required, then cost, then id, all
     * descending/stable so the pick is deterministic.
     */
    private function highestValidAffix(Character $character, string $affixType): ?ItemAffix
    {
        $enchantingLevel = $this->enchantingSkillLevel($character);
        $characterInt = $character->getInformation()->statMod('int');

        return ItemAffix::where('type', $affixType)
            ->where('randomly_generated', false)
            ->where('skill_level_required', '<=', $enchantingLevel)
            ->where('int_required', '<=', $characterInt)
            ->orderByDesc('skill_level_required')
            ->orderByDesc('cost')
            ->orderBy('id')
            ->first();
    }

    private function selectedItemIdsFromPlan(array $plan): array
    {
        $selectedItemIds = [];

        foreach ($plan as $key => $entry) {
            if (! is_array($entry) || ! isset($entry['selected_item_id']) || is_null($entry['selected_item_id'])) {
                continue;
            }

            $selectedItemIds[$key] = (int) $entry['selected_item_id'];
        }

        return $selectedItemIds;
    }

    public function active(Character $character): ?BatchCrafting
    {
        $activeId = BatchCrafting::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->max('id');

        if (is_null($activeId)) {
            return null;
        }

        return BatchCrafting::find($activeId);
    }

    public function visible(Character $character): ?BatchCrafting
    {
        $activeBatchCrafting = $this->active($character);

        if (! is_null($activeBatchCrafting)) {
            return $activeBatchCrafting;
        }

        $visibleId = BatchCrafting::where('character_id', $character->id)
            ->whereNull('panel_dismissed_at')
            ->where(function ($query) {
                $query->whereNotNull('completed_at')
                    ->orWhereNotNull('cancelled_at');
            })
            ->max('id');

        if (is_null($visibleId)) {
            return null;
        }

        return BatchCrafting::find($visibleId);
    }

    public function status(Character $character): array
    {
        $batchCrafting = $this->visible($character);

        if (is_null($batchCrafting)) {
            return [
                'active' => false,
                'is_running' => false,
                'is_visible' => false,
                'can_cancel' => false,
                'can_dismiss' => false,
                'completed' => false,
                'status' => 'idle',
                'show_info' => ! BatchCrafting::where('character_id', $character->id)->where('info_acknowledged', true)->exists(),
                'event_batch' => $this->eventBatchData($character),
                'craft_mode_availability' => $this->craftModeAvailability($character),
                'craft_experience_options' => $this->craftExperienceOptions($character)->all(),
            ];
        }

        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $timer = $this->timerDetails($batchCrafting);
        $actionLog = $batchCrafting->action_log ?? [];
        $progress = $batchCrafting->progress ?? [];
        $outcomeTotals = $progress['outcome_totals'] ?? [];
        $currentItemSnapshot = $this->currentItemSnapshot($batchCrafting);
        $batchCraftingSet = InventorySet::query()
            ->where('character_id', $character->id)
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();
        $progressPercent = $this->progressPercent($batchCrafting, $timer['progress_percent']);
        $craftEnchantSetPipeline = $this->craftEnchantSetPipelineStatus($progress);
        $retryState = $this->retryState($batchCrafting, $progress);

        return [
            'active' => $batchCrafting->isRunning(),
            'is_running' => $batchCrafting->isRunning(),
            'is_visible' => true,
            'can_cancel' => $batchCrafting->isRunning(),
            'can_dismiss' => ! $batchCrafting->isRunning(),
            'completed' => ! $batchCrafting->isRunning(),
            'status' => $batchCrafting->isRunning() ? 'running' : ($batchCrafting->status ?? 'completed'),
            'show_info' => ! BatchCrafting::where('character_id', $character->id)->where('info_acknowledged', true)->exists(),
            'event_batch' => $this->eventBatchData($character, $batchCrafting),
            'craft_mode_availability' => $this->craftModeAvailability($character),
            'craft_experience_options' => $this->craftExperienceOptions($character)->all(),
            'batch' => [
                'id' => $batchCrafting->id,
                'type' => $batchCrafting->batch_type,
                'batch_type' => $batchCrafting->batch_type,
                'batch_label' => $type->label(),
                'human_mode_label' => $this->humanModeLabel($batchCrafting),
                'disposition' => $batchCrafting->disposition,
                'started_at' => $batchCrafting->started_at?->toJSON(),
                'ends_at' => $batchCrafting->ends_at?->toJSON(),
                'ended_at' => $batchCrafting->completed_at?->toJSON(),
                'completed_at' => $batchCrafting->completed_at?->toJSON(),
                'elapsed_seconds' => $timer['elapsed_seconds'],
                'remaining_seconds' => $timer['remaining_seconds'],
                'elapsed_human' => $timer['elapsed_human'],
                'remaining_human' => $timer['remaining_human'],
                'progress_percent' => $progressPercent,
                'stop_reason' => $batchCrafting->ended_reason,
                'ended_reason' => $batchCrafting->ended_reason,
                'status' => $batchCrafting->status,
                'inventory_count' => $character->getInventoryCount(),
                'inventory_max' => $character->inventory_max,
                'inventory_remaining' => max(0, $character->inventory_max - $character->getInventoryCount()),
                'inventory_percent' => $character->inventory_max > 0
                    ? min(100, (int) floor(($character->getInventoryCount() / $character->inventory_max) * 100))
                    : 0,
                'alchemy_bag_count' => $character->getAlchemyBagCount(),
                'alchemy_bag_max' => $character->alchemy_bag_limit,
                'alchemy_bag_remaining' => max(0, $character->alchemy_bag_limit - $character->getAlchemyBagCount()),
                'mode' => $progress['craft_mode'] ?? $progress['alchemy_mode'] ?? $progress['trinketry_mode'] ?? $progress['enchant_mode'] ?? $progress['holy_oil_mode'] ?? null,
                'phase' => $progress['craft_enchant_phase'] ?? null,
                'craft_enchant_specific_surviving_crafted_count' => (int) ($progress['craft_enchant_specific_surviving_crafted_count'] ?? 0),
                'next_action' => $this->nextAction($batchCrafting),
                'last_action' => $this->lastAction($actionLog),
                'event_mode' => (bool) ($progress['event_mode'] ?? false),
                'event_action' => $progress['event_action'] ?? null,
                'event_type' => $progress['event_type'] ?? null,
                'event_step' => $progress['event_step'] ?? null,
                'event_actions_per_tick' => $progress['event_actions_per_tick'] ?? null,
                ...$this->experienceRateInfo($type, $progress),
                'event_goal_progress' => $this->eventGoalProgress($progress),
                'event_character_contribution' => $this->eventCharacterContribution($character, $progress),
                'event_enchant_phase' => $progress['event_enchant_phase'] ?? null,
                'event_fallback_phase' => $progress['event_fallback_phase'] ?? null,
                'event_current_phase_label' => $this->eventCurrentPhaseLabel($batchCrafting),
                'event_stop_reason' => $this->eventStopReason($batchCrafting),
                'event_fallback_crafted_this_tick' => $progress['event_fallback_crafted_this_tick'] ?? 0,
                'event_fallback_enchanted_this_tick' => $progress['event_fallback_enchanted_this_tick'] ?? 0,
                'event_crafting_xp_gained' => (int) ($progress['event_crafting_xp_gained'] ?? 0),
                'event_enchanting_xp_gained' => (int) ($progress['event_enchanting_xp_gained'] ?? 0),
                'requested_amount' => $this->requestedAmount($progress),
                'completed_amount' => $this->completedAmount($progress),
                'remaining_amount' => $this->remainingAmount($progress),
                'completion_summary' => $this->craftCompletionSummary($type, $progress),
                'selected_set' => $this->selectedSetSummary($character, $progress),
                'output_destination' => $progress['output_destination'] ?? null,
                'output_destination_label' => $this->outputDestinationLabel($character, $progress),
                'output_set' => $this->outputSetSummary($character, $progress),
                'chart_points' => $this->normalizedChartPoints($progress['chart_points'] ?? []),
                'current_item_name' => $currentItemSnapshot['name'] ?? null,
                'current_item_snapshot' => $currentItemSnapshot,
                'current_crafted_item_snapshot' => $this->latestActionSnapshot($actionLog, 'crafted_item'),
                'current_enchanted_item_snapshot' => $this->latestActionSnapshot($actionLog, 'enchanted_item'),
                'alchemy_current_item' => $this->alchemyCurrentItemSnapshot($progress, $actionLog),
                'trinketry_current_item' => $this->latestActionSnapshot($actionLog, 'trinketry_item'),
                'crafted_item_snapshots' => $this->snapshotList($progress, 'crafted_item_snapshots', $actionLog, 'crafted_item'),
                'enchanted_item_snapshots' => $this->snapshotList($progress, 'enchanted_item_snapshots', $actionLog, 'enchanted_item'),
                'alchemy_item_snapshots' => $this->snapshotList($progress, 'alchemy_item_snapshots', $actionLog, 'alchemy_item'),
                'trinketry_item_snapshots' => $this->snapshotList($progress, 'trinketry_item_snapshots', $actionLog, 'trinketry_item'),
                'holy_oil_target_item_snapshots' => $this->holyOilTargetSnapshots($progress, $actionLog),
                'gold_spent' => $this->goldSpent($actionLog, $batchCrafting),
                'gold_spent_total' => (int) ($progress['currency_totals']['gold_spent'] ?? 0),
                'gold_gained_total' => (int) ($progress['currency_totals']['gold_gained'] ?? 0),
                'gold_left' => (int) $character->gold,
                'gold_dust_spent_total' => (int) ($progress['currency_totals']['gold_dust_spent'] ?? 0),
                'gold_dust_gained_total' => (int) ($progress['currency_totals']['gold_dust_gained'] ?? 0),
                'gold_dust_left' => (int) $character->gold_dust,
                'copper_coins_spent_total' => (int) ($progress['currency_totals']['copper_coins_spent'] ?? 0),
                'copper_coins_left' => (int) $character->copper_coins,
                'shards_spent_total' => (int) ($progress['currency_totals']['shards_spent'] ?? 0),
                'shards_gained_total' => (int) ($progress['currency_totals']['shards_gained'] ?? 0),
                'shards_left' => (int) $character->shards,
                'listing_price_per_item' => $progress['listing_price'] ?? null,
                'total_listed_value' => (int) ($progress['currency_totals']['listed_value'] ?? 0),
                'potential_seller_net' => (int) round(((int) ($progress['currency_totals']['listed_value'] ?? 0)) * 0.95),
                'no_inventory_reason' => $progress['no_inventory_reason'] ?? null,
                'skills' => $this->relevantSkillsForBatch($character, $type, $progress, $batchCrafting->disposition),
                'skills_being_trained' => $this->skillsBeingTrained($character, $batchCrafting),
                'currency' => [
                    'type' => $type->requiredCurrency(),
                    'amount' => $this->currencyAmount($character, $type->requiredCurrency()),
                ],
                'counts' => [
                    'crafted' => $batchCrafting->crafted_count,
                    'sold' => $batchCrafting->sold_count,
                    'destroyed' => $batchCrafting->destroyed_count,
                    'listed' => $batchCrafting->listed_count,
                    'disenchanted' => (int) ($outcomeTotals['disenchanted'] ?? $this->countActionStatus($actionLog, 'disenchanted')),
                    'enchanted' => (int) ($outcomeTotals['enchanted'] ?? $progress['enchant_set_completed'] ?? $this->countActionStatus($actionLog, 'enchanted')),
                    'alchemy_processed' => (int) ($outcomeTotals['alchemy_processed'] ?? $this->countActionKey($actionLog, 'alchemy_item')),
                    'trinketry_processed' => (int) ($outcomeTotals['trinketry_processed'] ?? $this->countActionKey($actionLog, 'trinketry_item')),
                    'kept' => $batchCrafting->kept_count,
                    'applied' => $batchCrafting->applied_count,
                    'skipped' => $batchCrafting->skipped_count,
                    'failed' => $batchCrafting->failed_count,
                ],
                'craft_set_current_item' => $progress['craft_set_current_item'] ?? null,
                'craft_enchant_set_phase' => $progress['craft_enchant_set_phase'] ?? null,
                'craft_enchant_set_requested' => $progress['craft_enchant_set_requested'] ?? null,
                'craft_enchant_set_prefix_applied_count' => $progress['craft_enchant_set_prefix_applied_count'] ?? null,
                'craft_enchant_set_suffix_applied_count' => $progress['craft_enchant_set_suffix_applied_count'] ?? null,
                'craft_enchant_set_completed_final_count' => $progress['craft_enchant_set_completed_final_count'] ?? null,
                ...$craftEnchantSetPipeline,
                'craft_enchant_set_current_item' => $progress['craft_enchant_set_current_item'] ?? null,
                'craft_enchant_set_current_prefix' => $progress['craft_enchant_set_current_prefix'] ?? null,
                'craft_enchant_set_current_suffix' => $progress['craft_enchant_set_current_suffix'] ?? null,
                'craft_enchant_set_current_prefix_affix' => $progress['craft_enchant_set_current_prefix_affix'] ?? null,
                'craft_enchant_set_current_suffix_affix' => $progress['craft_enchant_set_current_suffix_affix'] ?? null,
                'enchant_set_total' => $progress['enchant_set_total'] ?? null,
                'enchant_set_completed' => $progress['enchant_set_completed'] ?? null,
                'enchant_set_skipped' => $progress['enchant_set_skipped'] ?? null,
                'enchant_set_current_item' => $progress['enchant_set_current_item'] ?? null,
                'enchant_affix_ids' => $progress['enchant_affix_ids'] ?? null,
                'enchant_affix_names' => $this->enchantAffixNames($progress),
                'enchant_affixes' => $this->enchantAffixDetails($progress),
                'int_stop_details' => $progress['int_stop_details'] ?? null,
                'holy_oil_eligible_items' => $progress['holy_oil_eligible_items'] ?? null,
                'holy_oil_total_stacks' => $progress['holy_oil_total_stacks'] ?? null,
                'holy_oil_requested_applications' => $progress['holy_oil_requested_applications'] ?? null,
                'holy_oil_completed_applications' => $progress['holy_oil_completed_applications'] ?? null,
                'holy_oil_remaining_applications' => $this->holyOilRemainingApplications($progress),
                'holy_oil_total_stat_bonus_applied' => (float) ($progress['holy_oil_total_stat_bonus_applied'] ?? 0),
                'holy_oil_total_devouring_darkness_bonus_applied' => (float) ($progress['holy_oil_total_devouring_darkness_bonus_applied'] ?? 0),
                'holy_oil_gold_dust_spent' => (int) ($progress['holy_oil_gold_dust_spent'] ?? 0),
                'holy_oil_skipped_items' => $batchCrafting->skipped_count,
                'holy_oil_selected_item_count' => count($batchCrafting->selected_items ?? []),
                'holy_oil_selected_oil_count' => count($batchCrafting->selected_oils ?? []),
                'holy_oil_current_target_item' => $progress['holy_oil_current_target_item'] ?? null,
                'holy_oil_current_oil_item' => $progress['holy_oil_current_oil_item'] ?? null,
                'holy_oil_application_results' => $progress['holy_oil_application_results'] ?? [],
                'kept_set_summary' => $this->keptSetSummary($batchCrafting),
                'amount_preview' => $this->amountPreview($character, $type, $progress, $disposition),
                'alchemy_amount_preview' => $this->alchemyAmountPreview($character, $type, $progress, $disposition),
                'holy_oil_selected_preview' => ($progress['holy_oil_mode'] ?? 'selected') === 'selected'
                    ? ($progress['holy_oil_application_plan'] ?? $this->holyOilsSelectedPreview($character, $type, $batchCrafting->selected_items ?? [], $batchCrafting->selected_oils ?? [], $progress))
                    : null,
                'holy_oil_set_preview' => ($progress['holy_oil_mode'] ?? 'selected') === 'set'
                    ? array_merge(
                        ['set_name' => $this->selectedSetSummary($character, $progress)['name'] ?? 'Inventory Set'],
                        $progress['holy_oil_application_plan'] ?? $this->holyOilsSetPreview($character, $type, $batchCrafting->selected_oils ?? [], $progress) ?? [],
                    )
                    : null,
                'batch_crafting_set' => [
                    'current_slots' => $batchCraftingSet?->currentSlotCount() ?? 0,
                    'max_slots' => $batchCraftingSet?->max_slots ?? InventorySet::BATCH_CRAFTING_MAX_SLOTS,
                    'remaining_slots' => $batchCraftingSet?->remainingSlots() ?? InventorySet::BATCH_CRAFTING_MAX_SLOTS,
                    'percent' => $this->craftedSetPercent($batchCraftingSet),
                ],
                'retry_state' => $retryState,
                'continuation_state' => $this->continuationState($batchCrafting, $progress),
                'action_log' => $actionLog,
                'action_history' => $actionLog,
            ],
        ];
    }

    /**
     * Persisted continuation state exposed to the frontend as the source of truth for
     * the active panel's countdown. Unlike retryState(), this stays visible across
     * destroyed/partial-enchant replacement cycles and "more finite work remains"
     * waits, not only plain failures.
     */
    private function continuationState(BatchCrafting $batchCrafting, array $progress): array
    {
        $active = $batchCrafting->isRunning() && (bool) ($progress['continuation_active'] ?? false);

        return [
            'active' => $active,
            'state' => $active ? ($progress['continuation_state'] ?? null) : null,
            'reason' => $active ? ($progress['continuation_reason'] ?? null) : null,
            'message' => $active ? ($progress['continuation_message'] ?? null) : null,
            'phase' => $active ? ($progress['continuation_phase'] ?? null) : null,
            'item' => $active ? ($progress['continuation_item'] ?? null) : null,
            'delay_seconds' => $active ? (int) ($progress['continuation_delay_seconds'] ?? 0) : 0,
            'next_attempt_at' => $active ? ($progress['next_attempt_at'] ?? null) : null,
        ];
    }

    public function process(BatchCrafting $batchCrafting): BatchCrafting
    {
        if (! $this->isContinuousFiniteMode($batchCrafting)) {
            return $this->processOneOperation($batchCrafting);
        }

        for ($operation = 0; $operation < self::ITEMS_PER_RECURRING_TICK; $operation++) {
            $batchCrafting = $this->processOneOperation($batchCrafting->refresh());

            if (! $this->isContinuousFiniteMode($batchCrafting)) {
                break;
            }
        }

        return $batchCrafting;
    }

    public function processOneOperation(BatchCrafting $batchCrafting): BatchCrafting
    {
        if (! $batchCrafting->isRunning()) {
            return $batchCrafting;
        }

        $character = $batchCrafting->character()->first();

        if ($character->is_dead) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::DIED);
        }

        if ($batchCrafting->ends_at <= now()) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::COMPLETED_DURATION);
        }

        $progress = $batchCrafting->progress ?? [];

        if (($progress['nothing_left'] ?? false) === true) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT);
        }

        if (($progress['all_oils_applied'] ?? false) === true) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::ALL_OILS_APPLIED);
        }

        if (($progress['no_oils_left'] ?? false) === true) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::NO_OILS_LEFT);
        }

        if (($progress['no_selected_items_left'] ?? false) === true) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::NO_SELECTED_ITEMS_LEFT);
        }

        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $currency = $progress['required_currency'] ?? $type->requiredCurrency();

        if ($type !== BatchCraftingType::TRINKETRY && $this->currencyAmount($character, $currency) <= 0) {
            return $this->complete($batchCrafting, $this->currencyEndReason($currency));
        }

        $currencyBefore = $this->currencyAmount($character, $currency);

        try {
            $result = $this->processor->processOneTick($batchCrafting, $character);
        } catch (Throwable $e) {
            $this->logger()->exceptionCaught($batchCrafting, $e, $this->exceptionLogContext($batchCrafting, $character));
            $this->reportBatchCraftingException($batchCrafting, $character, $e);

            return $this->complete($batchCrafting, BatchCraftingEndReason::FAILED);
        }

        $eventGoalJustCompleted = ($result['end_reason'] ?? null) === BatchCraftingEndReason::EVENT_GOAL_COMPLETE
            || (! isset($result['end_reason']) && $this->eventGoalCompletedAfterTick($batchCrafting->refresh()));

        if ($eventGoalJustCompleted) {
            $result['end_reason'] = $this->cycleOrEndEventGoal($batchCrafting->refresh(), $character);
        }

        if (isset($result['end_reason'])) {
            if ($result['end_reason'] === BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING) {
                $progress = $batchCrafting->refresh()->progress ?? [];
                $progress['int_stop_details'] = $this->intStopDetails($batchCrafting, $progress, $result['int_stop_affix_ids'] ?? []);
                $batchCrafting->update(['progress' => $progress]);
                $result['actions'] = array_map(function (array $action) use ($progress): array {
                    if (($action['status'] ?? null) !== 'stopped') {
                        return $action;
                    }

                    $action['int_stop_details'] = $progress['int_stop_details'];
                    $action['failure'] = $this->intStopMessage($progress['int_stop_details']);

                    return $action;
                }, $result['actions'] ?? []);
            }

            $counts = $result['counts'] ?? [];

            foreach ($counts as $column => $increment) {
                if (! in_array($column, [
                    'crafted_count',
                    'sold_count',
                    'destroyed_count',
                    'listed_count',
                    'kept_count',
                    'applied_count',
                    'skipped_count',
                    'failed_count',
                ])) {
                    continue;
                }

                $batchCrafting->increment($column, $increment);
            }

            if (! empty($result['actions'] ?? [])) {
                $this->appendActionLog($batchCrafting, $counts, $result['actions']);
                $this->logActions($batchCrafting, $result['actions']);
            }

            $this->recordProgressTotals($batchCrafting->refresh(), $counts, $result['actions'] ?? []);
            $this->recordChartPoint($batchCrafting->refresh(), $currency, $currencyBefore, $counts, $result['actions'] ?? []);

            return $this->complete($batchCrafting, $result['end_reason']);
        }

        $counts = $result['counts'] ?? [];

        foreach ($counts as $column => $increment) {
            if (! in_array($column, [
                'crafted_count',
                'sold_count',
                'destroyed_count',
                'listed_count',
                'kept_count',
                'applied_count',
                'skipped_count',
                'failed_count',
            ])) {
                continue;
            }

            $batchCrafting->update([
                $column => ((int) $batchCrafting->{$column}) + $increment,
            ]);
        }

        if (! empty($counts) || ! empty($result['actions'] ?? [])) {
            $this->appendActionLog($batchCrafting, $counts, $result['actions'] ?? []);
        }

        if (! empty($result['actions'] ?? [])) {
            $this->logActions($batchCrafting, $result['actions']);
        }

        $this->recordProgressTotals($batchCrafting->refresh(), $counts, $result['actions'] ?? []);
        $this->recordChartPoint($batchCrafting->refresh(), $currency, $currencyBefore, $counts, $result['actions'] ?? []);
        if ($this->isContinuousFiniteMode($batchCrafting->refresh())) {
            $latestFailedAction = collect(array_reverse($result['actions'] ?? []))->first(function (array $action): bool {
                return ($action['status'] ?? $this->actionStatus($action)) === 'failed';
            });
            $progress = array_merge($batchCrafting->refresh()->progress ?? [], [
                'continuation_active' => true,
                'continuation_state' => 'processing',
                'continuation_reason' => null,
                'continuation_message' => 'Batch Crafting is processing the next attempt now.',
                'continuation_phase' => null,
                'continuation_item' => null,
                'continuation_delay_seconds' => 0,
                'next_attempt_at' => null,
                'last_tick_failed_count' => (int) ($counts['failed_count'] ?? 0),
                'last_tick_had_failure' => (int) ($counts['failed_count'] ?? 0) > 0,
                'last_tick_failure_reason' => $latestFailedAction['failure'] ?? null,
                'last_tick_failure_phase' => $latestFailedAction['phase'] ?? null,
                'last_tick_failure_action' => $latestFailedAction['action'] ?? null,
                'last_tick_retry_delay_seconds' => 0,
            ]);
            $batchCrafting->update(['progress' => $progress]);
        } else {
            $this->applyRetryDelay($batchCrafting->refresh(), $counts, $result['actions'] ?? []);
        }

        event(new BatchCraftingStatusUpdated($batchCrafting->user_id));
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        if (! empty($counts)) {
            event(new AutomationLogUpdate($batchCrafting->user_id, $this->tickSummaryMessage($counts)));
        }

        return $batchCrafting->refresh();
    }

    /**
     * Per-tick summary shown in place of individual manual-style crafting/
     * enchanting/alchemy/trinketry/sell/list/disenchant messages, since a single
     * batch tick can process many items and firing one player-facing message per
     * item would spam the message log. Action history still carries the exact
     * outcome of every individual item.
     */
    private function tickSummaryMessage(array $counts): string
    {
        $labels = [
            'crafted_count' => 'crafted',
            'enchanted_count' => 'enchanted',
            'kept_count' => 'kept',
            'sold_count' => 'sold',
            'destroyed_count' => 'destroyed',
            'listed_count' => 'listed',
            'disenchanted_count' => 'disenchanted',
            'applied_count' => 'applied Holy Oil to',
            'used_count' => 'used',
            'failed_count' => 'failed to process',
            'skipped_count' => 'skipped',
        ];

        $parts = [];

        foreach ($labels as $column => $label) {
            $amount = (int) ($counts[$column] ?? 0);

            if ($amount > 0) {
                $parts[] = $label.' '.number_format($amount).($amount === 1 ? ' item' : ' items');
            }
        }

        if (empty($parts)) {
            return 'Batch crafting processed this chunk with no item changes.';
        }

        return 'Batch crafting update: '.implode(', ', $parts).'.';
    }

    /**
     * When a tick leaves normal-failure remaining work behind, the batch still has
     * finite remaining work (amount/set/holy oil modes), or a Craft/Craft and Enchant
     * experience-mode cycle is mid-way through its 6/6/6/5 chunks, retry quickly
     * instead of waiting for the full recurring delay. Open-ended experience modes
     * with no finite remaining amount, and experience cycles that just completed,
     * keep the recurring delay.
     */
    private function applyRetryDelay(BatchCrafting $batchCrafting, array $counts, array $actions): void
    {
        $progress = $batchCrafting->progress ?? [];
        $hadNormalFailureThisTick = (int) ($counts['failed_count'] ?? 0) > 0;
        $hadDestroyedEventEnchantThisTick = collect($actions)->contains(function (array $action): bool {
            return ($action['status'] ?? $this->actionStatus($action)) === 'destroyed'
                && in_array($action['action'] ?? null, ['event_enchant', 'event_fallback_enchant'], true);
        });
        $remaining = $this->remainingAmount($progress);
        $hasRemainingFiniteWork = ! is_null($remaining) && $remaining > 0;
        $hasIncompleteExperienceCycle = $this->hasIncompleteExperienceCycle($batchCrafting, $progress);

        $delaySeconds = ($hadNormalFailureThisTick || $hadDestroyedEventEnchantThisTick || $hasRemainingFiniteWork || $hasIncompleteExperienceCycle)
            ? self::IMMEDIATE_DELAY_SECONDS
            : self::RECURRING_DELAY_SECONDS;
        $progress['tick_delay_seconds'] = $delaySeconds;

        $latestFailedAction = null;

        foreach (array_reverse($actions) as $action) {
            $actionStatus = $action['status'] ?? $this->actionStatus($action);

            if ($actionStatus === 'failed') {
                $latestFailedAction = $action;

                break;
            }
        }

        $currentTickFailedCount = (int) ($counts['failed_count'] ?? 0);

        $progress['last_tick_failed_count'] = $currentTickFailedCount;
        $progress['last_tick_had_failure'] = $currentTickFailedCount > 0;
        $progress['last_tick_failure_reason'] = $latestFailedAction['failure'] ?? null;
        $progress['last_tick_failure_phase'] = $latestFailedAction['phase'] ?? null;
        $progress['last_tick_failure_action'] = $latestFailedAction['action'] ?? null;
        $progress['last_tick_retry_delay_seconds'] = $delaySeconds;

        $progress = array_merge($progress, $this->resolveContinuationForNextTick(
            $batchCrafting,
            $progress,
            $actions,
            $delaySeconds,
            $hasRemainingFiniteWork || $hasIncompleteExperienceCycle
        ));

        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Persistent continuation state visible to the frontend between ticks: which
     * reason is driving the next attempt (priority: latest partial-enchant-discard,
     * then latest destroyed Craft and Enchant item, then latest plain failure, then
     * "more finite work remains"), the exact next-attempt timestamp, and the
     * current phase/item so the panel never looks frozen while waiting.
     */
    private function resolveContinuationForNextTick(BatchCrafting $batchCrafting, array $progress, array $actions, int $delaySeconds, bool $hasRemainingWork): array
    {
        $latestPartialDiscarded = null;
        $latestDestroyed = null;
        $latestDestroyedEventEnchant = null;
        $latestFailed = null;

        foreach (array_reverse($actions) as $action) {
            $status = $action['status'] ?? $this->actionStatus($action);
            $actionType = (string) ($action['action'] ?? '');

            if (is_null($latestPartialDiscarded) && $status === 'partial_enchant_discarded') {
                $latestPartialDiscarded = $action;
            }

            $isCraftAndEnchantAction = str_contains($actionType, 'craft_and_enchant') || str_contains($actionType, 'craft_enchant_set_enchant');

            if (is_null($latestDestroyed) && $status === 'destroyed' && $isCraftAndEnchantAction) {
                $latestDestroyed = $action;
            }

            if (is_null($latestDestroyedEventEnchant) && $status === 'destroyed' && in_array($actionType, ['event_enchant', 'event_fallback_enchant'], true)) {
                $latestDestroyedEventEnchant = $action;
            }

            if (is_null($latestFailed) && $status === 'failed') {
                $latestFailed = $action;
            }
        }

        $reason = null;
        $message = null;

        if (! is_null($latestPartialDiscarded)) {
            $reason = 'recraft_partial_enchant';
            $message = 'The item did not receive every requested enchantment. It was discarded, and a replacement will be crafted and enchanted again.';
        } elseif (! is_null($latestDestroyed)) {
            $reason = 'recraft_destroyed_item';
            $message = 'The item shattered during enchanting. A replacement will be crafted and enchanted again.';
        } elseif (! is_null($latestFailed)) {
            $reason = 'retry_failed_attempt';
            $message = 'That attempt failed, but Batch Crafting is still running and will try again.';
        } elseif (! is_null($latestDestroyedEventEnchant)) {
            $reason = 'retry_failed_attempt';
            $message = 'The event item shattered while enchanting. Batch Crafting will continue with the next event item or craft a replacement.';
        } elseif ($batchCrafting->isRunning()) {
            $reason = 'continue_remaining_work';
            $message = $hasRemainingWork
                ? 'Batch Crafting is continuing with the remaining requested work.'
                : 'Batch Crafting is waiting before the next scheduled attempt.';
        }

        if (is_null($reason)) {
            return $this->clearedContinuationState();
        }

        return [
            'continuation_active' => true,
            'continuation_state' => 'waiting',
            'continuation_reason' => $reason,
            'continuation_message' => $message,
            'continuation_phase' => $progress['craft_enchant_set_phase'] ?? $progress['craft_enchant_phase'] ?? null,
            'continuation_item' => $this->continuationItemName($progress, $actions),
            'continuation_delay_seconds' => $delaySeconds,
            'next_attempt_at' => now()->addSeconds($delaySeconds)->toIso8601String(),
        ];
    }

    private function continuationItemName(array $progress, array $actions): ?string
    {
        foreach (array_reverse($actions) as $action) {
            $itemSnapshot = $action['crafted_item'] ?? $action['destroyed_item'] ?? $action['enchanted_item'] ?? null;

            if (! is_null($itemSnapshot['name'] ?? null)) {
                return $itemSnapshot['name'];
            }
        }

        return $progress['craft_enchant_set_current_item']['name']
            ?? $progress['craft_experience_current_item_snapshot']['name']
            ?? null;
    }

    private function clearedContinuationState(): array
    {
        return [
            'continuation_active' => false,
            'continuation_state' => null,
            'continuation_reason' => null,
            'continuation_message' => null,
            'continuation_phase' => null,
            'continuation_item' => null,
            'continuation_delay_seconds' => 0,
            'next_attempt_at' => null,
        ];
    }

    /**
     * True when this batch is a Craft or Craft and Enchant experience-mode run that
     * is part-way through its 23-action cycle (experience_cycle_actions > 0), meaning
     * more chunks are still owed before the cycle's steady-state recurring delay applies.
     */
    private function hasIncompleteExperienceCycle(BatchCrafting $batchCrafting, array $progress): bool
    {
        $type = BatchCraftingType::from($batchCrafting->batch_type);

        if (! in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true)) {
            return false;
        }

        if (($progress['craft_mode'] ?? 'experience') !== 'experience') {
            return false;
        }

        return (int) ($progress['experience_cycle_actions'] ?? 0) > 0;
    }

    private function craftEnchantSetPipelineStatus(array $progress): array
    {
        $requested = max(0, (int) ($progress['craft_enchant_set_requested'] ?? 0));

        $craftCompleted = isset($progress['craft_enchant_set_surviving_crafted_count'])
            ? min($requested, max(0, (int) $progress['craft_enchant_set_surviving_crafted_count']))
            : min($requested, max(0, (int) ($progress['craft_enchant_set_craft_index'] ?? 0)));
        $enchantCompleted = min($requested, max(0, (int) ($progress['craft_enchant_set_enchant_index'] ?? 0)));
        $finalizeCompleted = min($requested, max(0, (int) ($progress['craft_enchant_set_finalize_index'] ?? 0)));
        $queueCount = count($progress['craft_enchant_set_queue'] ?? []);
        $derivedTotalWorkUnits = ($requested > 0 ? $requested : $queueCount) * 3;
        $totalWorkUnits = max(0, (int) ($progress['craft_enchant_set_total_work_units'] ?? $derivedTotalWorkUnits));
        $derivedCompletedWorkUnits = $craftCompleted + $enchantCompleted + $finalizeCompleted;
        $completedWorkUnits = min($totalWorkUnits, max(0, (int) ($progress['craft_enchant_set_completed_work_units'] ?? $derivedCompletedWorkUnits)));
        $remainingWorkUnits = max(0, $totalWorkUnits - $completedWorkUnits);

        $overallPercent = $totalWorkUnits === 0
            ? 0
            : min(100, max(0, (int) floor(($completedWorkUnits / $totalWorkUnits) * 100)));

        return [
            'craft_enchant_set_craft_completed_count' => $craftCompleted,
            'craft_enchant_set_enchant_completed_count' => $enchantCompleted,
            'craft_enchant_set_finalize_completed_count' => $finalizeCompleted,
            'craft_enchant_set_total_work_units' => $totalWorkUnits,
            'craft_enchant_set_completed_work_units' => $completedWorkUnits,
            'craft_enchant_set_remaining_work_units' => $remainingWorkUnits,
            'craft_enchant_set_overall_percent' => $overallPercent,
        ];
    }

    /**
     * Retry state from only the most recent tick, never cumulative failure totals,
     * so a batch with old failures but no current retry condition reports inactive.
     */
    private function retryState(BatchCrafting $batchCrafting, array $progress): array
    {
        $failedCount = (int) ($progress['last_tick_failed_count'] ?? 0);
        $hadFailure = (bool) ($progress['last_tick_had_failure'] ?? false);
        $delaySeconds = (int) ($progress['last_tick_retry_delay_seconds'] ?? 0);

        $active = $batchCrafting->isRunning()
            && $hadFailure
            && $failedCount > 0
            && $delaySeconds === self::IMMEDIATE_DELAY_SECONDS
            && is_null($batchCrafting->ended_reason);

        return [
            'active' => $active,
            'failed_count' => $active ? $failedCount : 0,
            'delay_seconds' => $delaySeconds,
            'failure_reason' => $active ? ($progress['last_tick_failure_reason'] ?? null) : null,
            'failure_phase' => $active ? ($progress['last_tick_failure_phase'] ?? null) : null,
            'failure_action' => $active ? ($progress['last_tick_failure_action'] ?? null) : null,
        ];
    }

    /**
     * Marks a running batch as actively processing the next attempt, broadcast before
     * process() runs so the frontend never shows a stale "waiting" countdown while a
     * tick is genuinely in progress.
     */
    public function markProcessing(BatchCrafting $batchCrafting): BatchCrafting
    {
        if (! $batchCrafting->isRunning()) {
            return $batchCrafting;
        }

        $progress = $batchCrafting->progress ?? [];
        $isInitialStartDelay = ($progress['continuation_reason'] ?? null) === 'initial_start_delay'
            || ($progress['continuation_phase'] ?? null) === 'starting';
        $progress['continuation_active'] = true;
        $progress['continuation_state'] = 'processing';
        $progress['continuation_message'] = 'Batch Crafting is processing the next attempt now.';
        $progress['next_attempt_at'] = null;

        if ($isInitialStartDelay) {
            $progress['continuation_reason'] = null;
            $progress['continuation_phase'] = null;
            $progress['continuation_item'] = null;
            $progress['continuation_delay_seconds'] = 0;
        }

        $batchCrafting->update(['progress' => $progress]);

        event(new BatchCraftingStatusUpdated($batchCrafting->user_id));
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        return $batchCrafting->refresh();
    }

    public function cancel(Character $character): ?BatchCrafting
    {
        $batchCrafting = $this->active($character);

        if (is_null($batchCrafting)) {
            return null;
        }

        return $this->complete($batchCrafting, BatchCraftingEndReason::CANCELLED, ['cancelled_at' => now()]);
    }

    public function dismiss(Character $character): void
    {
        $batchCrafting = $this->visible($character);

        if (! is_null($batchCrafting) && ! $batchCrafting->isRunning()) {
            $batchCrafting->update(['panel_dismissed_at' => now()]);
            event(new BatchCraftingStatusUpdated($character->user_id));
            event(new BatchCraftingMonitoringUpdated($character->id));
        }
    }

    public function acknowledgeInfo(Character $character): void
    {
        BatchCrafting::updateOrCreate([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'completed_at' => null,
            'cancelled_at' => null,
            'status' => 'info',
        ], [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'info_acknowledged' => true,
            'started_at' => now(),
            'ends_at' => now(),
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::COMPLETED_DURATION->value,
            'panel_dismissed_at' => now(),
        ]);
    }

    public function completeForDeath(Character $character): void
    {
        $batchCrafting = $this->active($character);

        if (! is_null($batchCrafting)) {
            $this->complete($batchCrafting, BatchCraftingEndReason::DIED);
        }
    }

    private function complete(BatchCrafting $batchCrafting, BatchCraftingEndReason $reason, array $extra = []): BatchCrafting
    {
        $progress = $batchCrafting->progress ?? [];

        if ($reason === BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING && ! isset($progress['int_stop_details'])) {
            $progress['int_stop_details'] = $this->intStopDetails($batchCrafting, $progress);
        }

        $progress = array_merge($progress, $this->clearedContinuationState());

        $batchCrafting->update(array_merge([
            'completed_at' => now(),
            'ended_reason' => $reason->value,
            'status' => 'completed',
            'panel_dismissed_at' => null,
            'progress' => $progress,
        ], $extra));

        $reasonLabel = ucwords(str_replace('_', ' ', $reason->value));
        $playerMessage = 'Batch crafting has ended. Reason: '.$reasonLabel;

        if ($reason === BatchCraftingEndReason::CANCELLED) {
            $this->logger()->batchCancelled($batchCrafting);
            event(new AutomationLogUpdate($batchCrafting->user_id, 'Batch crafting was cancelled.'));
        } elseif ($reason === BatchCraftingEndReason::FAILED) {
            $playerMessage = 'Batch crafting stopped because of a server issue. This has been logged for investigation.';
            $this->logger()->hardStop($batchCrafting, $reason);
            event(new AutomationLogUpdate($batchCrafting->user_id, $playerMessage));
        } elseif ($reason === BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING) {
            $details = $progress['int_stop_details'];
            $playerMessage = $this->intStopMessage($details);
            $this->logger()->hardStop($batchCrafting, $reason);
            event(new AutomationLogUpdate($batchCrafting->user_id, $playerMessage));
        } elseif ($reason === BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT && isset($progress['invalid_plan_message'])) {
            $playerMessage = $progress['invalid_plan_message'];
            $this->logger()->hardStop($batchCrafting, $reason);
            event(new AutomationLogUpdate($batchCrafting->user_id, $playerMessage));
        } elseif ($reason === BatchCraftingEndReason::CRAFT_ENCHANT_SET_TARGET_SET_CHANGED) {
            $playerMessage = 'Batch crafting stopped because the target set is no longer valid. It may have been equipped, emptied, or deleted after this batch started.';
            $this->logger()->hardStop($batchCrafting, $reason);
            event(new AutomationLogUpdate($batchCrafting->user_id, $playerMessage));
        } elseif (in_array($reason, [
            BatchCraftingEndReason::COMPLETED_DURATION,
            BatchCraftingEndReason::AMOUNT_REACHED,
            BatchCraftingEndReason::ALL_OILS_APPLIED,
            BatchCraftingEndReason::CRAFT_SET_COMPLETE,
            BatchCraftingEndReason::ENCHANT_SET_COMPLETE,
            BatchCraftingEndReason::CRAFT_ENCHANT_SET_COMPLETE,
        ], true)) {
            $this->logger()->batchCompleted($batchCrafting, $reason);
            event(new AutomationLogUpdate($batchCrafting->user_id, 'Batch crafting completed. Reason: '.$reasonLabel));
        } else {
            $this->logger()->hardStop($batchCrafting, $reason);
            event(new AutomationLogUpdate($batchCrafting->user_id, 'Batch crafting stopped. Reason: '.$reasonLabel));
        }

        $user = $batchCrafting->user()->first();

        if (! is_null($user) && $reason !== BatchCraftingEndReason::TRINKETRY_INSUFFICIENT_CURRENCIES) {
            event(new ServerMessageEvent($user, $playerMessage));
        }

        event(new BatchCraftingStatusUpdated($batchCrafting->user_id));
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        return $batchCrafting->refresh();
    }

    private function intStopDetails(BatchCrafting $batchCrafting, array $progress, array $intStopAffixIds = []): array
    {
        $affixIds = collect($intStopAffixIds);

        if ($affixIds->filter()->isEmpty()) {
            $affixIds = collect($progress['enchant_affix_ids'] ?? [])->merge([
                $progress['craft_enchant_set_current_prefix_affix']['id'] ?? null,
                $progress['craft_enchant_set_current_suffix_affix']['id'] ?? null,
                $progress['craft_experience_current_prefix_affix']['id'] ?? null,
                $progress['craft_experience_current_suffix_affix']['id'] ?? null,
            ]);
        }

        $affixIds = $affixIds->filter()
            ->unique()
            ->values();
        $characterInt = (int) $batchCrafting->character()->first()->getInformation()->statMod('int');
        $affixes = ItemAffix::whereIn('id', $affixIds)->get()
            ->filter(fn (ItemAffix $affix): bool => $affix->int_required > $characterInt)
            ->map(fn (ItemAffix $affix): array => [
                'id' => $affix->id,
                'name' => $affix->name,
                'type' => $affix->type,
                'int_required' => (int) $affix->int_required,
            ])->values()->all();
        $requiredInt = collect($affixes)->max('int_required');

        return [
            'character_int' => $characterInt,
            'required_int' => $requiredInt,
            'missing_int' => is_null($requiredInt) ? null : $requiredInt - $characterInt,
            'affixes' => $affixes,
        ];
    }

    private function intStopMessage(array $details): string
    {
        if (is_null($details['required_int']) || empty($details['affixes'])) {
            return 'Batch Crafting stopped: Intelligence too low. The exact blocking enchantment details could not be resolved.';
        }

        $affixDetails = collect($details['affixes'])->map(fn (array $affix): string => $affix['name'].' ('.$affix['type'].') requires '.number_format($affix['int_required']).' INT')->implode('; ');

        return 'Batch Crafting stopped: Intelligence too low. Required INT: '.number_format($details['required_int']).'. Current INT: '.number_format($details['character_int']).'. Missing INT: '.number_format($details['missing_int']).'. Blocking enchantments: '.$affixDetails.'.';
    }

    private function logActions(BatchCrafting $batchCrafting, array $actions): void
    {
        foreach ($actions as $action) {
            $this->logger()->actionAttempted($batchCrafting, $action);
            $status = $this->actionStatus($action);

            if ($status === 'failed') {
                $this->logger()->failedRoll($batchCrafting, $action);
            } elseif ($status === 'skipped') {
                $this->logger()->skippedAction($batchCrafting, $action);
            } else {
                $this->logger()->actionSucceeded($batchCrafting, $action);
            }

            if (isset($action['disposition'])) {
                $this->logger()->dispositionApplied($batchCrafting, $action);
            }
        }
    }

    private function logger(): BatchCraftingLogger
    {
        return $this->batchCraftingLogger;
    }

    private function appendActionLog(BatchCrafting $batchCrafting, array $counts, array $actions = []): void
    {
        $entries = [];
        $actions = empty($actions) ? [[]] : $actions;

        foreach ($actions as $action) {
            $entry = [
                'ts' => now()->toJSON(),
                'timestamp' => now()->toJSON(),
                'type' => $batchCrafting->batch_type,
                'action_type' => $action['action'] ?? $batchCrafting->batch_type,
                'status' => $this->actionStatus($action),
            ];

            foreach ($counts as $column => $increment) {
                $entry[str_replace('_count', '', $column)] = $increment;
            }

            $entries[] = array_merge($entry, $action);
        }

        $log = $batchCrafting->action_log ?? [];
        $log = array_merge($log, $entries);

        $batchCrafting->update(['action_log' => $log]);
    }

    private function recordProgressTotals(BatchCrafting $batchCrafting, array $counts, array $actions): void
    {
        $progress = $batchCrafting->progress ?? [];
        $totals = array_merge([
            'successful' => 0,
            'crafted' => 0,
            'enchanted' => 0,
            'disenchanted' => 0,
            'alchemy_processed' => 0,
            'trinketry_processed' => 0,
            'applied' => 0,
            'skipped' => 0,
            'failed' => 0,
            'destroyed' => 0,
        ], $progress['outcome_totals'] ?? []);

        $totals['crafted'] += (int) ($counts['crafted_count'] ?? 0);
        $totals['enchanted'] += (int) ($counts['enchanted_count'] ?? 0);
        $totals['disenchanted'] += (int) ($counts['disenchanted_count'] ?? 0);
        $totals['applied'] += (int) ($counts['applied_count'] ?? 0);
        $totals['skipped'] += (int) ($counts['skipped_count'] ?? 0);
        $totals['failed'] += (int) ($counts['failed_count'] ?? 0);
        $totals['destroyed'] += (int) ($counts['destroyed_count'] ?? 0);

        foreach ($actions as $action) {
            if (! in_array($this->actionStatus($action), ['failed', 'destroyed', 'skipped'], true)) {
                $totals['successful']++;
            }
        }

        foreach ($actions as $action) {
            if (isset($action['alchemy_item'])) {
                $totals['alchemy_processed']++;
            }

            if (isset($action['trinketry_item'])) {
                $totals['trinketry_processed']++;
            }

            $this->appendSnapshotToProgress($progress, 'crafted_item_snapshots', $action['crafted_item'] ?? null);
            $this->appendSnapshotToProgress($progress, 'enchanted_item_snapshots', $action['enchanted_item'] ?? null);
            $this->appendSnapshotToProgress($progress, 'alchemy_item_snapshots', $action['alchemy_item'] ?? null);
            $this->appendSnapshotToProgress($progress, 'trinketry_item_snapshots', $action['trinketry_item'] ?? null);
            $this->appendSnapshotToProgress($progress, 'holy_oil_target_item_snapshots', $action['oil_application']['target_item'] ?? null);
            $progress['event_crafting_xp_gained'] = ((int) ($progress['event_crafting_xp_gained'] ?? 0)) + (int) ($action['crafting_xp_gained'] ?? 0);
            $progress['event_enchanting_xp_gained'] = ((int) ($progress['event_enchanting_xp_gained'] ?? 0)) + (int) ($action['enchanting_xp_gained'] ?? 0);
        }

        $progress['outcome_totals'] = $totals;

        $batchCrafting->update(['progress' => $progress]);
    }

    private function appendSnapshotToProgress(array &$progress, string $key, mixed $snapshot): void
    {
        if (! is_array($snapshot)) {
            return;
        }

        $progress[$key] = $progress[$key] ?? [];
        $progress[$key][] = [
            'display_name' => $snapshot['name'] ?? 'Unknown Item',
            'quantity' => 1,
            'snapshot' => $snapshot,
            'slot_id' => $snapshot['slot_id_for_modal'] ?? null,
            'crafted_at' => now()->toJSON(),
        ];
    }

    private function recordChartPoint(BatchCrafting $batchCrafting, string $currency, int $currencyBefore, array $counts, array $actions): void
    {
        $outcomeCounts = $this->chartOutcomeCounts($counts, $actions);

        $goldGained = collect($actions)->sum(fn (array $action) => (int) ($action['gold_gained'] ?? 0));
        $goldDustGained = collect($actions)->sum(fn (array $action) => (int) ($action['gold_dust_gained'] ?? 0));
        $gainedInSpentCurrency = match ($currency) {
            'gold' => $goldGained,
            'gold_dust' => $goldDustGained,
            default => 0,
        };

        $currencyAfter = $this->currencyAmount($batchCrafting->character()->first(), $currency);
        $spent = max(0, $currencyBefore - $currencyAfter + $gainedInSpentCurrency);

        $goldSpent = $currency === 'gold' ? $spent : 0;
        $goldDustSpent = $currency === 'gold_dust' ? $spent : 0;
        $shardsSpent = $currency === 'shards' ? $spent : 0;

        $progress = $batchCrafting->progress ?? [];
        $chartPoints = $progress['chart_points'] ?? ['currency' => [], 'outcomes' => [], 'gold_dust' => []];
        $tick = count($chartPoints['currency']) + 1;

        $listedValue = collect($actions)->sum(fn (array $action) => (int) ($action['listed_price'] ?? 0));

        $chartPoints['currency'][] = [
            'tick' => $tick,
            'gold_spent' => $goldSpent,
            'gold_gained' => $goldGained,
            'gold_dust_spent' => $goldDustSpent
                + collect($actions)->sum(fn (array $action) => (int) ($action['gold_dust_spent'] ?? 0)),
            'gold_dust_gained' => $goldDustGained,
            'copper_coins_spent' => collect($actions)->sum(fn (array $action) => (int) ($action['copper_coins_spent'] ?? 0)),
            'shards_spent' => $shardsSpent,
            'shards_gained' => 0,
            'listed_value' => $listedValue,
        ];
        $chartPoints['outcomes'][] = [
            'tick' => $tick,
            'successful' => $outcomeCounts['successful'],
            'failed' => $outcomeCounts['failed'],
            'destroyed' => $outcomeCounts['destroyed'],
            'skipped' => $outcomeCounts['skipped'],
        ];
        $chartPoints['gold_dust'][] = ['tick' => $tick, 'gained' => $goldDustGained];

        $progress['chart_points'] = $chartPoints;
        $progress['currency_totals'] = $this->accumulatedCurrencyTotals($progress, $currency, $spent, $actions);
        $batchCrafting->update(['progress' => $progress]);
    }

    private function normalizedChartPoints(mixed $chartPoints): array
    {
        if (! is_array($chartPoints)) {
            $chartPoints = [];
        }

        $currencyPoints = is_array($chartPoints['currency'] ?? null) ? $chartPoints['currency'] : [];
        $outcomePoints = is_array($chartPoints['outcomes'] ?? null) ? $chartPoints['outcomes'] : [];
        $goldDustPoints = is_array($chartPoints['gold_dust'] ?? null) ? $chartPoints['gold_dust'] : [];

        return [
            'currency' => array_values(array_map(fn (mixed $point): array => [
                'tick' => $this->chartInteger($point, 'tick'),
                'gold_spent' => $this->chartInteger($point, 'gold_spent'),
                'gold_gained' => $this->chartInteger($point, 'gold_gained'),
                'gold_dust_spent' => $this->chartInteger($point, 'gold_dust_spent'),
                'gold_dust_gained' => $this->chartInteger($point, 'gold_dust_gained'),
                'copper_coins_spent' => $this->chartInteger($point, 'copper_coins_spent'),
                'shards_spent' => $this->chartInteger($point, 'shards_spent'),
                'shards_gained' => $this->chartInteger($point, 'shards_gained'),
                'listed_value' => $this->chartInteger($point, 'listed_value'),
            ], $currencyPoints)),
            'outcomes' => array_values(array_map(fn (mixed $point): array => [
                'tick' => $this->chartInteger($point, 'tick'),
                'successful' => $this->chartInteger($point, 'successful', 'success'),
                'failed' => $this->chartInteger($point, 'failed', 'failure'),
                'destroyed' => $this->chartInteger($point, 'destroyed'),
                'skipped' => $this->chartInteger($point, 'skipped'),
            ], $outcomePoints)),
            'gold_dust' => array_values(array_map(fn (mixed $point): array => [
                'tick' => $this->chartInteger($point, 'tick'),
                'gained' => $this->chartInteger($point, 'gained'),
            ], $goldDustPoints)),
        ];
    }

    private function chartInteger(mixed $point, string $key, ?string $legacyKey = null): int
    {
        if (! is_array($point)) {
            return 0;
        }

        $value = $point[$key] ?? ($legacyKey === null ? 0 : ($point[$legacyKey] ?? 0));

        return is_numeric($value) ? (int) $value : 0;
    }

    private function accumulatedCurrencyTotals(array $progress, string $currency, int $spent, array $actions): array
    {
        $totals = $progress['currency_totals'] ?? [
            'gold_spent' => 0,
            'gold_gained' => 0,
            'gold_dust_spent' => 0,
            'gold_dust_gained' => 0,
            'copper_coins_spent' => 0,
            'shards_spent' => 0,
            'shards_gained' => 0,
            'listed_value' => 0,
        ];

        if ($currency === 'gold') {
            $totals['gold_spent'] += $spent;
        } elseif ($currency === 'gold_dust') {
            $totals['gold_dust_spent'] += $spent;
        } elseif ($currency === 'shards') {
            $totals['shards_spent'] += $spent;
        }

        $totals['gold_gained'] += collect($actions)->sum(fn (array $action) => (int) ($action['gold_gained'] ?? 0));
        $totals['gold_dust_gained'] += collect($actions)->sum(fn (array $action) => (int) ($action['gold_dust_gained'] ?? 0));
        $totals['gold_dust_spent'] += collect($actions)->sum(fn (array $action) => (int) ($action['gold_dust_spent'] ?? 0));
        $totals['copper_coins_spent'] += collect($actions)->sum(fn (array $action) => (int) ($action['copper_coins_spent'] ?? 0));
        $totals['listed_value'] = ((int) ($totals['listed_value'] ?? 0)) + collect($actions)->sum(fn (array $action) => (int) ($action['listed_price'] ?? 0));

        return $totals;
    }

    private function disenchantingSkillProgressIfApplicable(Character $character, BatchCraftingType $type, string $disposition): array
    {
        if ($type !== BatchCraftingType::CRAFT_AND_ENCHANT) {
            return [];
        }

        if (! in_array($disposition, [
            BatchCraftingDisposition::DISENCHANT->value,
            BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
        ], true)) {
            return [];
        }

        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::DISENCHANTING->value))
            ->with('baseSkill')
            ->first();

        if (is_null($skill) || $skill->level >= $skill->max_level) {
            return [];
        }

        $xpPercent = $skill->xp_max > 0 ? min(100, (int) floor(($skill->xp / $skill->xp_max) * 100)) : 0;

        return [[
            'key' => 'disenchanting',
            'name' => $skill->name,
            'level' => $skill->level,
            'max_level' => $skill->max_level,
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'xp_percent' => $xpPercent,
            'is_maxed' => false,
        ]];
    }

    private function enchantAffixNames(array $progress): array
    {
        $affixIds = $progress['enchant_affix_ids'] ?? [];

        if (empty($affixIds)) {
            return [];
        }

        return ItemAffix::whereIn('id', $affixIds)->pluck('name')->values()->all();
    }

    private function enchantAffixDetails(array $progress): array
    {
        $affixIds = $progress['enchant_affix_ids'] ?? [];

        if (empty($affixIds)) {
            return [];
        }

        return ItemAffix::whereIn('id', $affixIds)->get()->map(fn (ItemAffix $affix) => $affix->toArray())->values()->all();
    }

    private function holyOilRemainingApplications(array $progress): ?int
    {
        $requested = $progress['holy_oil_requested_applications'] ?? null;
        $completed = $progress['holy_oil_completed_applications'] ?? null;

        if (is_null($requested) || is_null($completed)) {
            return null;
        }

        return max(0, (int) $requested - (int) $completed);
    }

    private function craftingSkillData(Character $character, BatchCraftingType $type): array
    {
        $skillNameMap = $this->relevantSkillNameMap($type);

        if (empty($skillNameMap)) {
            return [];
        }

        return $this->craftingSkillDataForMap($character, $skillNameMap);
    }

    private function craftingSkillDataForMap(Character $character, array $skillNameMap): array
    {
        if (empty($skillNameMap)) {
            return [];
        }

        $skills = $character->skills()->with('baseSkill')->get();
        $result = [];

        foreach ($skillNameMap as $key => $lookup) {
            $skill = $skills->first(function ($skill) use ($lookup) {
                if ($lookup['by'] === 'name') {
                    return $skill->name === $lookup['name'];
                }

                return ($skill->baseSkill->type ?? null) === $lookup['type'];
            });

            if (is_null($skill)) {
                continue;
            }

            $maxLevel = $skill->max_level;
            $isMaxed = $skill->level >= $maxLevel;
            $xpPercent = $isMaxed ? 100 : ($skill->xp_max > 0 ? min(100, (int) floor(($skill->xp / $skill->xp_max) * 100)) : 0);

            $result[] = [
                'key' => $key,
                'name' => $skill->name,
                'level' => $skill->level,
                'max_level' => $maxLevel,
                'current_xp' => $skill->xp,
                'next_level_xp' => $skill->xp_max,
                'xp_percent' => $xpPercent,
                'is_maxed' => $isMaxed,
            ];
        }

        return $result;
    }

    private function skillsBeingTrained(Character $character, BatchCrafting $batchCrafting): array
    {
        return $this->craftingSkillData($character, BatchCraftingType::from($batchCrafting->batch_type));
    }

    private function relevantSkillsForBatch(Character $character, BatchCraftingType $type, array $progress, string $disposition): array
    {
        $craftMode = $progress['craft_mode'] ?? 'experience';

        if (in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true) && $craftMode === 'specific_item') {
            return $this->specificItemSkillData($character, $type, $progress, $disposition);
        }

        return array_merge(
            $this->craftingSkillData($character, $type),
            $this->disenchantingSkillProgressIfApplicable($character, $type, $disposition)
        );
    }

    private function specificItemSkillData(Character $character, BatchCraftingType $type, array $progress, string $disposition): array
    {
        $item = Item::find($progress['specific_item_id'] ?? null);
        $craftingSkillKey = is_null($item) ? null : $this->craftingSkillKeyForItem($item);

        if (is_null($craftingSkillKey)) {
            return $this->craftingSkillData($character, $type);
        }

        $relevantKeys = array_merge([$craftingSkillKey], $type === BatchCraftingType::CRAFT_AND_ENCHANT ? ['enchanting'] : []);

        $skillNameMap = array_intersect_key($this->relevantSkillNameMap($type), array_flip($relevantKeys));

        $result = $this->craftingSkillDataForMap($character, $skillNameMap);

        if ($type === BatchCraftingType::CRAFT_AND_ENCHANT) {
            $result = array_merge($result, $this->disenchantingSkillProgressIfApplicable($character, $type, $disposition));
        }

        return $result;
    }

    private function craftingSkillKeyForItem(Item $item): ?string
    {
        return match ($this->craftingSkillName($item->type)) {
            'Weapon Crafting' => 'weapon',
            'Armour Crafting' => 'armour',
            'Ring Crafting' => 'ring',
            'Spell Crafting' => 'spell',
            default => null,
        };
    }

    private function craftExperienceOptions(Character $character)
    {
        $skills = $character->skills()->with('baseSkill')->get();

        return collect([
            ['value' => 'weapon', 'label' => 'Weapon Crafting', 'skill_name' => 'Weapon Crafting', 'batch_type' => BatchCraftingType::CRAFT->value],
            ['value' => 'armour', 'label' => 'Armour Crafting', 'skill_name' => 'Armour Crafting', 'batch_type' => BatchCraftingType::CRAFT->value],
            ['value' => 'ring', 'label' => 'Ring Crafting', 'skill_name' => 'Ring Crafting', 'batch_type' => BatchCraftingType::CRAFT->value],
            ['value' => 'spell', 'label' => 'Spell Crafting', 'skill_name' => 'Spell Crafting', 'batch_type' => BatchCraftingType::CRAFT->value],
            ['value' => 'enchanting', 'label' => 'Enchanting', 'skill_name' => 'Enchanting', 'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value],
            ['value' => 'disenchanting', 'label' => 'Disenchanting', 'skill_name' => 'Disenchanting', 'batch_type' => null],
            ['value' => 'trinketry', 'label' => 'Trinketry', 'skill_name' => 'Trinketry', 'batch_type' => BatchCraftingType::TRINKETRY->value],
            ['value' => 'alchemy', 'label' => 'Alchemy', 'skill_name' => 'Alchemy', 'batch_type' => BatchCraftingType::ALCHEMY->value],
            ['value' => 'gem_crafting', 'label' => 'Gem Crafting', 'skill_name' => 'Gem Crafting', 'batch_type' => null],
        ])->map(function (array $option) use ($skills): array {
            $skill = $skills->first(fn ($characterSkill) => $characterSkill->name === $option['skill_name'] || $characterSkill->baseSkill?->name === $option['skill_name']);

            return [
                'value' => $option['value'],
                'label' => $option['label'],
                'batch_type' => $option['batch_type'],
                'skill_id' => $skill?->id,
                'skill_name' => $option['skill_name'],
                'current_level' => $skill?->level ?? 0,
                'max_level' => $skill?->max_level ?? 0,
                'current_xp' => $skill?->xp ?? 0,
                'required_xp' => $skill?->xp_max ?? 0,
                'progress_percent' => is_null($skill) || $skill->xp_max <= 0 ? 0 : min(100, (int) floor(($skill->xp / $skill->xp_max) * 100)),
                'is_maxed' => ! is_null($skill) && $skill->level >= $skill->max_level,
                'is_available' => ! is_null($skill) && $skill->level < $skill->max_level && ! is_null($option['batch_type']),
            ];
        })->filter(fn (array $option): bool => $option['is_available'])->values();
    }

    private function craftModeAvailability(Character $character): array
    {
        $skills = $character->skills()->with('baseSkill')->get();
        $craftingSkillNames = ['Weapon Crafting', 'Armour Crafting', 'Ring Crafting', 'Spell Crafting'];
        $craftingCanGainXp = collect($craftingSkillNames)->contains(function (string $skillName) use ($skills): bool {
            $skill = $skills->first(fn ($skill) => $skill->name === $skillName || $skill->baseSkill?->name === $skillName);

            return is_null($skill) || $skill->level < $skill->max_level;
        });
        $enchantingSkill = $skills->first(fn ($skill) => $skill->baseSkill?->type === SkillTypeValue::ENCHANTING->value || $skill->name === 'Enchanting' || $skill->baseSkill?->name === 'Enchanting');
        $enchantingCanGainXp = is_null($enchantingSkill) || $enchantingSkill->level < $enchantingSkill->max_level;

        return [
            'can_craft_for_experience' => $craftingCanGainXp,
            'can_craft_and_enchant_for_experience' => $craftingCanGainXp || $enchantingCanGainXp,
        ];
    }

    private function relevantSkillNameMap(BatchCraftingType $type): array
    {
        $craftingSkills = [
            'weapon' => ['by' => 'name', 'name' => 'Weapon Crafting'],
            'armour' => ['by' => 'name', 'name' => 'Armour Crafting'],
            'ring' => ['by' => 'name', 'name' => 'Ring Crafting'],
            'spell' => ['by' => 'name', 'name' => 'Spell Crafting'],
        ];

        return match ($type) {
            BatchCraftingType::CRAFT => $craftingSkills,
            BatchCraftingType::CRAFT_AND_ENCHANT => array_merge($craftingSkills, [
                'enchanting' => ['by' => 'type', 'type' => SkillTypeValue::ENCHANTING->value],
            ]),
            BatchCraftingType::ENCHANT => [
                'enchanting' => ['by' => 'type', 'type' => SkillTypeValue::ENCHANTING->value],
            ],
            BatchCraftingType::ALCHEMY => [
                'alchemy' => ['by' => 'type', 'type' => SkillTypeValue::ALCHEMY->value],
            ],
            BatchCraftingType::TRINKETRY => [
                'trinketry' => ['by' => 'name', 'name' => 'Trinketry'],
            ],
            BatchCraftingType::HOLY_OILS => [],
        };
    }

    private function requestedAmount(array $progress): ?int
    {
        return $progress['craft_amount']
            ?? $progress['alchemy_amount']
            ?? $progress['holy_oil_requested_applications']
            ?? $progress['craft_set_requested']
            ?? $progress['enchant_set_total']
            ?? $progress['craft_enchant_set_requested']
            ?? null;
    }

    private function completedAmount(array $progress): ?int
    {
        return $progress['craft_specific_count']
            ?? $progress['craft_enchant_specific_count']
            ?? $progress['alchemy_amount_count']
            ?? $progress['holy_oil_completed_applications']
            ?? $progress['craft_set_completed']
            ?? $progress['enchant_set_completed']
            ?? $progress['craft_enchant_set_completed_final_count']
            ?? null;
    }

    private function remainingAmount(array $progress): ?int
    {
        $requested = $this->requestedAmount($progress);
        $completed = $this->completedAmount($progress);

        if (is_null($requested) || is_null($completed)) {
            return null;
        }

        return max(0, (int) $requested - (int) $completed);
    }

    private function craftCompletionSummary(BatchCraftingType $type, array $progress): ?string
    {
        if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && ($progress['craft_mode'] ?? 'experience') === 'craft_enchant_set') {
            $requested = (int) ($progress['craft_enchant_set_requested'] ?? 0);
            $completed = (int) ($progress['craft_enchant_set_completed_final_count'] ?? 0);

            if ($requested <= 0) {
                return null;
            }

            if ($completed >= $requested) {
                return 'all';
            }

            if ($completed <= 0) {
                return 'none';
            }

            return 'some';
        }

        if ($type !== BatchCraftingType::CRAFT) {
            return null;
        }

        if (! in_array($progress['craft_mode'] ?? 'experience', ['specific_item', 'craft_set'], true)) {
            return null;
        }

        $requested = $this->requestedAmount($progress);
        $completed = $this->completedAmount($progress);

        if (is_null($requested) || $requested <= 0 || is_null($completed)) {
            return null;
        }

        if ($completed >= $requested) {
            return 'all';
        }

        if ($completed <= 0) {
            return 'none';
        }

        return 'some';
    }

    private function currentItemSnapshot(BatchCrafting $batchCrafting): ?array
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['craft_mode'] ?? null) === 'experience' && isset($progress['craft_experience_current_item_snapshot'])) {
            return $progress['craft_experience_current_item_snapshot'];
        }

        if (($progress['craft_mode'] ?? null) === 'specific_item') {
            $item = Item::find($progress['specific_item_id'] ?? null);

            if (is_null($item)) {
                return null;
            }

            return $this->itemSnapshot($item);
        }

        if (($progress['alchemy_mode'] ?? null) === 'amount') {
            $item = Item::find($progress['alchemy_item_id'] ?? null);

            if (! is_null($item)) {
                return $this->itemSnapshot($item);
            }
        }

        $lastSnapshot = collect($batchCrafting->action_log ?? [])
            ->reverse()
            ->map(fn (array $entry) => $entry['enchanted_item'] ?? $entry['crafted_item'] ?? $entry['alchemy_item'] ?? $entry['trinketry_item'] ?? $entry['oil_application']['target_item'] ?? $entry['kept_item'] ?? $entry['sold_item'] ?? $entry['destroyed_item'] ?? $entry['disenchanted_item'] ?? null)
            ->filter()
            ->first();

        return $lastSnapshot;
    }

    private function latestActionSnapshot(array $actionLog, string $key): ?array
    {
        return collect($actionLog)
            ->reverse()
            ->map(fn (array $entry) => $entry[$key] ?? null)
            ->filter()
            ->first();
    }

    private function alchemyCurrentItemSnapshot(array $progress, array $actionLog): ?array
    {
        $item = Item::find($progress['alchemy_item_id'] ?? null);

        if (! is_null($item)) {
            return $this->itemSnapshot($item);
        }

        return $this->latestActionSnapshot($actionLog, 'alchemy_item');
    }

    private function snapshotList(array $progress, string $progressKey, array $actionLog, string $actionKey): array
    {
        if (! empty($progress[$progressKey]) && is_array($progress[$progressKey])) {
            return array_values($progress[$progressKey]);
        }

        return $this->actionItemSnapshots($actionLog, $actionKey);
    }

    private function actionItemSnapshots(array $actionLog, string $actionKey): array
    {
        return collect($actionLog)
            ->map(fn (array $entry) => $entry[$actionKey] ?? null)
            ->filter()
            ->map(function (array $snapshot): array {
                return [
                    'display_name' => $snapshot['name'] ?? 'Unknown Item',
                    'quantity' => 1,
                    'snapshot' => $snapshot,
                    'slot_id' => $snapshot['slot_id_for_modal'] ?? null,
                    'crafted_at' => $snapshot['crafted_at'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function holyOilTargetSnapshots(array $progress, array $actionLog): array
    {
        if (! empty($progress['holy_oil_target_item_snapshots']) && is_array($progress['holy_oil_target_item_snapshots'])) {
            return array_values($progress['holy_oil_target_item_snapshots']);
        }

        return collect($actionLog)
            ->map(fn (array $entry) => $entry['oil_application']['target_item'] ?? null)
            ->filter()
            ->map(function (array $snapshot): array {
                return [
                    'display_name' => $snapshot['name'] ?? 'Unknown Item',
                    'quantity' => 1,
                    'snapshot' => $snapshot,
                    'slot_id' => $snapshot['slot_id_for_modal'] ?? null,
                    'crafted_at' => $snapshot['crafted_at'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function countActionKey(array $actionLog, string $actionKey): int
    {
        return collect($actionLog)->filter(fn (array $entry) => isset($entry[$actionKey]))->count();
    }

    private function goldSpent(array $actionLog, BatchCrafting $batchCrafting): int
    {
        $fromCurrency = collect($actionLog)->sum(function (array $entry): int {
            $currency = $entry['currency'] ?? null;

            if (! is_array($currency)) {
                return 0;
            }

            return max(0, (int) (($currency['before']['gold'] ?? 0) - ($currency['current']['gold'] ?? 0)));
        });

        if ($fromCurrency > 0) {
            return $fromCurrency;
        }

        $progress = $batchCrafting->progress ?? [];
        $item = Item::find($progress['specific_item_id'] ?? null);
        $completed = $progress['craft_specific_count'] ?? $progress['craft_enchant_specific_count'] ?? 0;

        return is_null($item) ? 0 : (int) $item->cost * (int) $completed;
    }

    private function humanModeLabel(BatchCrafting $batchCrafting): string
    {
        $progress = $batchCrafting->progress ?? [];

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT->value && ($progress['craft_mode'] ?? 'experience') === 'specific_item') {
            return 'Craft Amount';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT->value && ($progress['craft_mode'] ?? 'experience') === 'experience') {
            return 'Craft For Experience';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT->value && ($progress['craft_mode'] ?? 'experience') === 'craft_set') {
            return 'Craft Set';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value && ($progress['craft_mode'] ?? 'experience') === 'specific_item') {
            return 'Craft and Enchant Amount';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value && ($progress['craft_mode'] ?? 'experience') === 'experience') {
            return 'Craft and Enchant for Experience';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::CRAFT_AND_ENCHANT->value && ($progress['craft_mode'] ?? 'experience') === 'craft_enchant_set') {
            return 'Craft and Enchant Set';
        }

        if ($batchCrafting->batch_type === BatchCraftingType::ENCHANT->value && ($progress['enchant_mode'] ?? 'event') === 'event') {
            return 'Enchant For Event';
        }

        return BatchCraftingType::from($batchCrafting->batch_type)->label();
    }

    private function lastAction(array $actionLog): ?string
    {
        $last = collect($actionLog)->last();

        if (is_null($last)) {
            return null;
        }

        return ucwords(str_replace('_', ' ', $last['action_type'] ?? $last['action'] ?? 'batch action'));
    }

    private function selectedSetSummary(Character $character, array $progress): ?array
    {
        $setId = $progress['selected_set_id'] ?? null;

        if (is_null($setId)) {
            return null;
        }

        $set = InventorySet::where('id', $setId)->where('character_id', $character->id)->first();

        if (is_null($set)) {
            return null;
        }

        return [
            'id' => $set->id,
            'name' => $this->inventorySetDisplayName($character, $set),
            'current_slots' => $set->currentSlotCount(),
            'max_slots' => $set->max_slots,
            'remaining_slots' => $set->remainingSlots(),
        ];
    }

    /**
     * Status-facing summary of the selected inventory_set output destination, distinct
     * from selectedSetSummary() (which reads the unrelated selected_set_id used by
     * Enchant Set/Holy Oils Set).
     */
    private function outputSetSummary(Character $character, array $progress): ?array
    {
        if ($this->resolvedOutputDestination($progress) !== 'inventory_set') {
            return null;
        }

        $set = InventorySet::where('id', $progress['output_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set)) {
            return null;
        }

        return [
            'id' => $set->id,
            'name' => $this->inventorySetDisplayName($character, $set),
            'current_slots' => $set->currentSlotCount(),
            'max_slots' => $set->max_slots,
            'remaining_slots' => $set->remainingSlots(),
        ];
    }

    private function amountPreview(Character $character, BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): ?array
    {
        if (($progress['craft_mode'] ?? null) !== 'specific_item') {
            return null;
        }

        if (! in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true)) {
            return null;
        }

        $item = Item::find($progress['specific_item_id'] ?? null);

        if (is_null($item)) {
            return null;
        }

        $isCraftAndEnchant = $type === BatchCraftingType::CRAFT_AND_ENCHANT;
        $requested = (int) ($progress['craft_amount'] ?? 0);
        $completed = (int) ($progress['craft_specific_count'] ?? $progress['craft_enchant_specific_count'] ?? 0);
        $remainingRequested = max(0, $requested - $completed);
        $perItemCost = (int) $item->cost;

        $enchantCostPerItem = 0;
        $prefixName = null;
        $suffixName = null;
        $enchantHasFailureRisk = false;

        if ($isCraftAndEnchant) {
            $affixIds = array_values(array_filter($progress['enchant_affix_ids'] ?? [], fn ($id) => ! is_null($id)));

            if (! empty($affixIds)) {
                $enchantCostPerItem = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $item->id);
                $affixes = ItemAffix::whereIn('id', $affixIds)->get();
                $prefixName = $affixes->firstWhere('type', 'prefix')?->name;
                $suffixName = $affixes->firstWhere('type', 'suffix')?->name;
                $enchantHasFailureRisk = $this->enchantingSkillLevel($character) < 400;
            }
        }

        $totalPerItemCost = $perItemCost + $enchantCostPerItem;
        $affordableByGold = $totalPerItemCost > 0 ? intdiv((int) $character->gold, $totalPerItemCost) : $remainingRequested;

        $destinationCapacity = $this->destinationCapacityPreview($character, $type, $progress, $disposition);
        $destinationRemaining = $destinationCapacity['remaining'] ?? $remainingRequested;

        $capacityLimit = is_null($destinationCapacity) ? $remainingRequested : $destinationRemaining;
        $effectiveCraftableAmount = max(0, min($remainingRequested, $affordableByGold, $capacityLimit));

        return [
            'selected_item' => $this->itemSnapshot($item),
            'requested_amount' => $requested,
            'completed_amount' => $completed,
            'remaining_requested_amount' => $remainingRequested,
            'per_item_cost' => $perItemCost,
            'enchant_cost_per_item' => $enchantCostPerItem,
            'total_per_item_cost' => $totalPerItemCost,
            'total_cost' => $totalPerItemCost * $remainingRequested,
            'available_gold' => (int) $character->gold,
            'prefix_affix_name' => $prefixName,
            'suffix_affix_name' => $suffixName,
            'enchant_can_destroy_item' => $isCraftAndEnchant,
            'enchant_has_failure_risk' => $enchantHasFailureRisk,
            'destination' => $destinationCapacity['destination'] ?? null,
            'destination_label' => $destinationCapacity['destination_label'] ?? null,
            'destination_current_slots' => $destinationCapacity['current'] ?? 0,
            'destination_max_slots' => $destinationCapacity['max'] ?? 0,
            'destination_remaining_slots' => $destinationRemaining,
            'effective_craftable_amount' => $effectiveCraftableAmount,
            'capped' => $effectiveCraftableAmount < $remainingRequested,
        ];
    }

    private function alchemyAmountPreview(Character $character, BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): ?array
    {
        if ($type !== BatchCraftingType::ALCHEMY || ($progress['alchemy_mode'] ?? null) !== 'amount') {
            return null;
        }

        $item = Item::find($progress['alchemy_item_id'] ?? null);

        if (is_null($item)) {
            return null;
        }

        $requested = (int) ($progress['alchemy_amount'] ?? 0);
        $completed = (int) ($progress['alchemy_amount_count'] ?? 0);
        $remainingRequested = max(0, $requested - $completed);

        $goldDustCost = (int) $item->gold_dust_cost;
        $shardsCost = (int) $item->shards_cost;

        if ($character->classType()->isMerchant()) {
            $goldDustCost = (int) floor($goldDustCost - $goldDustCost * 0.10);
            $shardsCost = (int) floor($shardsCost - $shardsCost * 0.10);
        }

        if ($character->classType()->isArcaneAlchemist()) {
            $goldDustCost = (int) floor($goldDustCost - $goldDustCost * 0.15);
            $shardsCost = (int) floor($shardsCost - $shardsCost * 0.15);
        }

        $affordableByGoldDust = $goldDustCost > 0 ? intdiv((int) $character->gold_dust, $goldDustCost) : $remainingRequested;
        $affordableByShards = $shardsCost > 0 ? intdiv((int) $character->shards, $shardsCost) : $remainingRequested;
        $bagRemaining = max(0, $character->alchemy_bag_limit - $character->getAlchemyBagCount());

        $capacityLimit = $this->keepsOutputInAlchemyBag($disposition) ? $bagRemaining : $remainingRequested;
        $effectiveCraftableAmount = max(0, min($remainingRequested, $affordableByGoldDust, $affordableByShards, $capacityLimit));

        return [
            'selected_item' => $this->itemSnapshot($item),
            'requested_amount' => $requested,
            'completed_amount' => $completed,
            'remaining_requested_amount' => $remainingRequested,
            'gold_dust_cost_per_item' => $goldDustCost,
            'shards_cost_per_item' => $shardsCost,
            'total_gold_dust_cost' => $goldDustCost * $remainingRequested,
            'total_shards_cost' => $shardsCost * $remainingRequested,
            'available_gold_dust' => (int) $character->gold_dust,
            'available_shards' => (int) $character->shards,
            'bag_current' => $character->getAlchemyBagCount(),
            'bag_max' => $character->alchemy_bag_limit,
            'bag_remaining' => $bagRemaining,
            'effective_craftable_amount' => $effectiveCraftableAmount,
            'capped' => $effectiveCraftableAmount < $remainingRequested,
        ];
    }

    private function holyOilsSelectedPreview(Character $character, BatchCraftingType $type, array $selectedItemIds, array $selectedOilIds, array $progress): ?array
    {
        if ($type !== BatchCraftingType::HOLY_OILS || ($progress['holy_oil_mode'] ?? 'selected') === 'set') {
            return null;
        }

        if (empty($selectedItemIds) && empty($selectedOilIds)) {
            return null;
        }

        return $this->holyOilApplicationPlan($character, $selectedItemIds, $selectedOilIds, $progress);
    }

    private function holyOilsSetPreview(Character $character, BatchCraftingType $type, array $selectedOilIds, array $progress): ?array
    {
        if ($type !== BatchCraftingType::HOLY_OILS || ($progress['holy_oil_mode'] ?? 'selected') !== 'set') {
            return null;
        }

        $set = InventorySet::where('id', $progress['selected_set_id'] ?? 0)->where('character_id', $character->id)->first();

        if (is_null($set)) {
            return null;
        }

        return array_merge(
            ['set_name' => $this->inventorySetDisplayName($character, $set)],
            $this->holyOilApplicationPlan($character, [], $selectedOilIds, $progress),
        );
    }

    private function holyOilApplicationPlan(Character $character, array $selectedItemIds, array $selectedOilIds, array $progress): array
    {
        $isSetMode = ($progress['holy_oil_mode'] ?? 'selected') === 'set';
        if ($isSetMode) {
            $targetSet = InventorySet::where('id', $progress['selected_set_id'] ?? 0)
                ->where('character_id', $character->id)
                ->first();
            $targetSlots = is_null($targetSet)
                ? collect()
                : $targetSet->slots()->with('item')->orderBy('id')->get();
        } else {
            $targetSlots = is_null($character->inventory)
                ? collect()
                : $character->inventory->slots()->whereIn('id', $selectedItemIds)->with('item')->orderBy('id')->get();
        }
        $targetSlots = $targetSlots
            ->filter(fn ($slot) => ! is_null($slot->item) && ! in_array($slot->item->type, ['trinket', 'artifact'], true))
            ->filter(fn ($slot) => $slot->item->holy_stacks_applied < $slot->item->holy_stacks)
            ->values();
        $oilSlotsById = AlchemyBagSlot::whereIn('id', $selectedOilIds)
            ->where('character_id', $character->id)
            ->with('item')
            ->get()
            ->keyBy('id');
        $oilUnits = [];

        foreach ($selectedOilIds as $oilSlotId) {
            $oilSlot = $oilSlotsById->get($oilSlotId);

            if (is_null($oilSlot) || is_null($oilSlot->item)) {
                continue;
            }

            for ($quantity = 0; $quantity < (int) $oilSlot->amount; $quantity++) {
                $oilUnits[] = $oilSlot;
            }
        }

        $selectedOilsAvailable = count($oilUnits);
        $remainingGoldDust = (int) $character->gold_dust;
        $plannedItems = [];
        $applicationSequence = [];
        $unappliedReason = null;
        $stopPlanning = false;

        foreach ($targetSlots as $targetSlot) {
            $currentStacks = (int) $targetSlot->item->holy_stacks_applied;
            $maximumStacks = (int) $targetSlot->item->holy_stacks;
            $itemApplications = [];
            $itemCost = 0;

            while ($currentStacks + count($itemApplications) < $maximumStacks && ! empty($oilUnits)) {
                $oilSlot = $oilUnits[0];
                $cost = $this->holyItemService->getCost($targetSlot->item, $oilSlot->item);

                if ($cost > $remainingGoldDust) {
                    $unappliedReason = 'Insufficient Gold Dust for the next selected Holy Oil and target item.';
                    $stopPlanning = true;

                    break;
                }

                array_shift($oilUnits);
                $remainingGoldDust -= $cost;
                $itemCost += $cost;
                $application = [
                    'target_slot_id' => $targetSlot->id,
                    'oil_slot_id' => $oilSlot->id,
                    'gold_dust_cost' => $cost,
                ];
                $itemApplications[] = $application;
                $applicationSequence[] = $application;
            }

            if (! empty($itemApplications)) {
                $plannedItems[] = [
                    'item' => $this->itemSnapshot($targetSlot->item),
                    'full_item_details' => $this->itemTransformer->transform($targetSlot->item),
                    'target_slot_id' => $targetSlot->id,
                    'current_stacks' => $currentStacks,
                    'planned_applications' => count($itemApplications),
                    'resulting_stacks' => $currentStacks + count($itemApplications),
                    'maximum_stacks' => $maximumStacks,
                    'exact_gold_dust_cost' => $itemCost,
                    'oils_consumed' => count($itemApplications),
                ];
            }

            if ($stopPlanning) {
                break;
            }
        }

        if (is_null($unappliedReason) && ! empty($oilUnits)) {
            $unappliedReason = $targetSlots->isEmpty()
                ? 'No eligible target items have remaining Holy Oil stacks.'
                : 'Every eligible target item will reach its maximum Holy Oil stacks.';
        }

        $applicationsPlanned = count($applicationSequence);
        $oilsNotApplicable = max(0, $selectedOilsAvailable - $applicationsPlanned);

        return [
            'items' => $plannedItems,
            'application_sequence' => $applicationSequence,
            'selected_oils_available' => $selectedOilsAvailable,
            'applications_planned' => $applicationsPlanned,
            'items_affected' => count($plannedItems),
            'exact_gold_dust_required' => (int) $character->gold_dust - $remainingGoldDust,
            'gold_dust_available' => (int) $character->gold_dust,
            'oils_not_applicable' => $oilsNotApplicable,
            'unapplied_reason' => $oilsNotApplicable > 0 ? $unappliedReason : null,
            'capped' => $oilsNotApplicable > 0,
        ];
    }

    private function craftedSetPercent(?InventorySet $batchCraftingSet): int
    {
        if (is_null($batchCraftingSet) || $batchCraftingSet->max_slots <= 0) {
            return 0;
        }

        return min(100, (int) floor(($batchCraftingSet->currentSlotCount() / $batchCraftingSet->max_slots) * 100));
    }

    private function itemSnapshot(Item $item): array
    {
        return [
            'slot_id' => null,
            'item_id' => $item->id,
            'item_id_for_modal' => null,
            'slot_id_for_modal' => null,
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
            'holy_stacks_applied' => $item->holy_stacks_applied ?? 0,
            'affix_count' => $item->affix_count ?? 0,
            'is_unique' => (bool) ($item->is_unique ?? false),
            'is_mythic' => (bool) ($item->is_mythic ?? false),
            'is_cosmic' => (bool) ($item->is_cosmic ?? false),
            'can_view' => false,
            'full_item_details' => $item->type === 'alchemy'
                ? (new UsableItemTransformer())->transform($item)
                : $this->itemTransformer->transform($item),
        ];
    }

    private function currencyAmount(Character $character, string $currency): int
    {
        return (int) ($character->{$currency} ?? 0);
    }

    private function currencyLabel(string $currency): string
    {
        return match ($currency) {
            'gold_dust' => 'Gold Dust',
            'shards' => 'Shards',
            'gold' => 'Gold',
            default => str_replace('_', ' ', ucwords($currency, '_')),
        };
    }

    private function currencySourceHint(string $currency): ?string
    {
        return match ($currency) {
            'gold_dust' => 'Gold Dust is awarded by the daily lottery and by disenchanting items.',
            'shards' => 'Shards are awarded by battle rewards and by selling gems.',
            default => null,
        };
    }

    private function missingRequiredCurrencyMessage(Character $character, BatchCraftingType $type): string
    {
        $currency = $type->requiredCurrency();
        $label = $this->currencyLabel($currency);
        $source = $this->currencySourceHint($currency);
        $message = $type->label().' requires '.$label.' to start. Required to start: 1 '.$label.'. You have '.number_format($this->currencyAmount($character, $currency)).' '.$label.'.';

        if (! is_null($source)) {
            $message .= ' '.$source;
        }

        return $message;
    }

    private function currencyEndReason(string $currency): BatchCraftingEndReason
    {
        return match ($currency) {
            'gold' => BatchCraftingEndReason::NO_GOLD,
            'gold_dust' => BatchCraftingEndReason::NO_GOLD_DUST,
            'shards' => BatchCraftingEndReason::NO_SHARDS,
            default => BatchCraftingEndReason::NO_REQUIRED_CURRENCY,
        };
    }

    private function timerDetails(BatchCrafting $batchCrafting): array
    {
        $startedAt = $batchCrafting->started_at;
        $endsAt = $batchCrafting->ends_at;

        if (is_null($startedAt) || is_null($endsAt)) {
            return [
                'elapsed_seconds' => 0,
                'remaining_seconds' => 0,
                'elapsed_human' => $this->formatSeconds(0),
                'remaining_human' => $this->formatSeconds(0),
                'progress_percent' => 0,
            ];
        }

        $now = $batchCrafting->isRunning() ? now() : ($batchCrafting->completed_at ?? now());
        $elapsedSeconds = max(0, $startedAt->diffInSeconds($now, false));
        $remainingSeconds = $batchCrafting->isRunning() ? max(0, $now->diffInSeconds($endsAt, false)) : 0;
        $totalSeconds = max(1, $startedAt->diffInSeconds($endsAt));
        $progressPercent = min(100, max(0, (int) floor(($elapsedSeconds / $totalSeconds) * 100)));

        return [
            'elapsed_seconds' => $elapsedSeconds,
            'remaining_seconds' => $remainingSeconds,
            'elapsed_human' => $this->formatSeconds($elapsedSeconds),
            'remaining_human' => $this->formatSeconds($remainingSeconds),
            'progress_percent' => $progressPercent,
        ];
    }

    private function progressPercent(BatchCrafting $batchCrafting, int $timerProgressPercent): int
    {
        $progress = $batchCrafting->progress ?? [];
        $requested = $this->requestedAmount($progress);
        $completed = $this->completedAmount($progress);

        if (is_null($requested) || is_null($completed) || (int) $requested < 1) {
            return $timerProgressPercent;
        }

        return min(100, (int) floor(((int) $completed / (int) $requested) * 100));
    }

    private function formatSeconds(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return $hours.'h '.$minutes.'m '.$remainingSeconds.'s';
        }

        if ($minutes > 0) {
            return $minutes.'m '.$remainingSeconds.'s';
        }

        return $remainingSeconds.'s';
    }

    private function actionStatus(array $action): string
    {
        foreach (['disenchanted', 'kept', 'sold', 'destroyed', 'listed', 'applied', 'crafted', 'enchanted', 'failed', 'skipped'] as $status) {
            if (isset($action[$status.'_item']) || isset($action[$status.'_count'])) {
                return $status;
            }
        }

        if (isset($action['enchanted'])) {
            return 'enchanted';
        }

        if (isset($action['disenchanted_item'])) {
            return 'disenchanted';
        }

        if (isset($action['oil_application'])) {
            return 'applied';
        }

        if (isset($action['failure'])) {
            return 'failed';
        }

        if (isset($action['enchanted_item'])) {
            return 'enchanted';
        }

        if (isset($action['crafted_item']) || isset($action['trinketry_item']) || isset($action['alchemy_item'])) {
            return 'crafted';
        }

        return 'skipped';
    }

    /**
     * Success/failure chart counts, one outcome per action row so a single
     * action cannot be counted as both a success and a failure.
     *
     * @return array{successful: int, failed: int, destroyed: int, skipped: int}
     */
    private function chartOutcomeCounts(array $counts, array $actions): array
    {
        if (empty($actions)) {
            $successful = ((int) ($counts['crafted_count'] ?? 0)
                + (int) ($counts['enchanted_count'] ?? 0)
                + (int) ($counts['sold_count'] ?? 0)
                + (int) ($counts['listed_count'] ?? 0)
                + (int) ($counts['kept_count'] ?? 0)
                + (int) ($counts['disenchanted_count'] ?? 0)
                + (int) ($counts['applied_count'] ?? 0));

            return [
                'successful' => $successful,
                'failed' => (int) ($counts['failed_count'] ?? 0),
                'destroyed' => (int) ($counts['destroyed_count'] ?? 0),
                'skipped' => (int) ($counts['skipped_count'] ?? 0),
            ];
        }

        $outcomes = [
            'successful' => 0,
            'failed' => 0,
            'destroyed' => 0,
            'skipped' => 0,
        ];

        foreach ($actions as $action) {
            $status = $action['status'] ?? $this->actionStatus($action);

            if (isset($outcomes[$status])) {
                $outcomes[$status]++;

                continue;
            }

            $outcomes['successful']++;
        }

        return $outcomes;
    }

    private function countActionStatus(array $actionLog, string $status): int
    {
        return collect($actionLog)->filter(fn (array $entry) => ($entry['status'] ?? null) === $status)->count();
    }

    private function keptSetSummary(BatchCrafting $batchCrafting): ?array
    {
        if ($batchCrafting->ended_reason !== BatchCraftingEndReason::NO_GOLD->value) {
            return null;
        }

        $items = collect($batchCrafting->action_log ?? [])
            ->pluck('kept_item')
            ->filter()
            ->values()
            ->all();

        if (empty($items)) {
            return null;
        }

        return [
            'message' => 'Oops, you ran out of gold, but we kept the best set we could build for you. Any completed pieces are in your inventory.',
            'items' => $items,
        ];
    }

    private function nextAction(BatchCrafting $batchCrafting): ?string
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['event_mode'] ?? false) === true && ($progress['event_action'] ?? null) === 'enchant') {
            return match ($progress['event_enchant_phase'] ?? 'enchant_event_inventory') {
                'enchant_event_inventory' => 'double-enchant event-crafted items',
                'craft_fallback_set' => 'craft fallback items and double-enchant them',
                'enchant_fallback_set' => 'double-enchant fallback items',
                default => 'wait for next tick',
            };
        }

        if (($progress['craft_enchant_phase'] ?? null) === 'enchant') {
            return 'enchant';
        }

        return match ($batchCrafting->batch_type) {
            BatchCraftingType::CRAFT->value => 'craft',
            BatchCraftingType::CRAFT_AND_ENCHANT->value => 'craft',
            BatchCraftingType::ENCHANT->value => 'enchant',
            BatchCraftingType::ALCHEMY->value => 'alchemy',
            BatchCraftingType::HOLY_OILS->value => 'holy_oil',
            BatchCraftingType::TRINKETRY->value => 'trinketry',
            default => null,
        };
    }

    private function tickDelaySeconds(BatchCraftingType $type, array $progress): int
    {
        return self::RECURRING_DELAY_SECONDS;
    }

    private function firstActionDelayMessage(int $seconds): string
    {
        if ($seconds > 0 && $seconds % 60 === 0) {
            $minutes = intdiv($seconds, 60);

            return $minutes === 1 ? '1 minute' : $minutes.' minutes';
        }

        return $seconds === 1 ? '1 second' : $seconds.' seconds';
    }

    private function eventCurrentPhaseLabel(BatchCrafting $batchCrafting): ?string
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['event_mode'] ?? false) !== true) {
            return null;
        }

        if (! $batchCrafting->isRunning()) {
            return $this->eventStopReason($batchCrafting);
        }

        if (($progress['event_action'] ?? null) === 'craft') {
            return 'Crafting event items';
        }

        return match ($progress['event_enchant_phase'] ?? 'enchant_event_inventory') {
            'enchant_event_inventory' => 'Enchanting event-crafted items',
            'craft_fallback_set' => 'Crafting fallback items',
            'enchant_fallback_set' => 'Double-enchanting fallback items',
            default => 'Waiting for next tick',
        };
    }

    private function eventStopReason(BatchCrafting $batchCrafting): ?string
    {
        return match ($batchCrafting->ended_reason) {
            BatchCraftingEndReason::EVENT_GOAL_COMPLETE->value => 'Event goal complete. Batch stopped.',
            BatchCraftingEndReason::EVENT_NOT_RUNNING->value => 'Event is no longer running. Batch stopped.',
            BatchCraftingEndReason::EVENT_WRONG_MAP->value => 'You are no longer on the event map. Batch stopped.',
            BatchCraftingEndReason::EVENT_STEP_CHANGED->value => 'Event phase changed. Batch stopped.',
            BatchCraftingEndReason::EVENT_NO_EVENT_ITEMS_TO_ENCHANT->value => 'Stopped because no event items and fallback cannot continue.',
            BatchCraftingEndReason::EVENT_NO_AFFIXES->value => 'Stopped because no valid enchantments are available.',
            default => null,
        };
    }

    private function eventBatchData(Character $character, ?BatchCrafting $batchCrafting = null): array
    {
        $craftGoal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);
        $enchantGoal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);
        $goal = $craftGoal ?? $enchantGoal;
        $event = $goal?->event;

        return [
            'can_craft_for_event' => ! is_null($craftGoal),
            'can_enchant_for_event' => ! is_null($enchantGoal),
            'event_type' => $event?->type,
            'event_name' => is_null($event) ? null : (new EventType($event->type))->getNameForEvent(),
            'current_step' => $event?->current_event_goal_step,
            'goal_remaining' => is_null($goal) ? null : $this->goalRemaining($goal),
            'actions_per_tick' => self::EVENT_ITEMS_PER_SET_TICK,
            'tick_rate_seconds' => self::RECURRING_DELAY_SECONDS,
            'max_runtime_hours' => self::DURATION_HOURS,
            'active_event_mode' => (bool) (($batchCrafting?->progress ?? [])['event_mode'] ?? false),
            'crafting_skills_maxed' => $this->areAllCraftingSkillsMaxed($character),
            'alchemy_locked' => $this->isAlchemyLocked($character),
            'alchemy_maxed' => $this->isSkillMaxedByName($character, 'Alchemy'),
            'trinketry_maxed' => $this->isSkillMaxedByName($character, 'Trinketry'),
            'enchanting_maxed' => $this->isEnchantingMaxed($character),
        ];
    }

    private function isAlchemyLocked(Character $character): bool
    {
        $alchemy = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();

        if (is_null($alchemy)) {
            return true;
        }

        $skill = Skill::where('game_skill_id', $alchemy->id)
            ->where('character_id', $character->id)
            ->first();

        if (! is_null($skill)) {
            return (bool) $skill->is_locked;
        }

        return true;
    }

    private function experienceRateInfo(BatchCraftingType $type, array $progress): array
    {
        if (($progress['event_mode'] ?? false) === true) {
            if (($progress['event_action'] ?? null) === 'craft') {
                return [
                    'actions_per_minute' => self::EVENT_ITEMS_PER_SET_TICK,
                    'experience_rate_label' => 'Crafts 1 full event set, 23 items, per minute unless a specific event item is selected.',
                ];
            }

            if (($progress['event_action'] ?? null) === 'enchant') {
                return [
                    'actions_per_minute' => self::EVENT_ITEMS_PER_SET_TICK,
                    'experience_rate_label' => 'Enchants up to 23 event items per minute.',
                ];
            }
        }

        if ($type === BatchCraftingType::CRAFT && ($progress['craft_mode'] ?? null) === 'experience') {
            return [
                'actions_per_minute' => self::ITEMS_PER_FULL_SET,
                'experience_rate_label' => 'Crafts 1 full set, 23 items, per minute.',
            ];
        }

        if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && ($progress['craft_mode'] ?? null) === 'experience') {
            return [
                'actions_per_minute' => self::ITEMS_PER_FULL_SET,
                'experience_rate_label' => 'Crafts and double-enchants 1 full set, 23 items, per minute.',
            ];
        }

        if ($type === BatchCraftingType::ALCHEMY && ($progress['alchemy_mode'] ?? null) === 'experience') {
            return [
                'actions_per_minute' => self::ITEMS_PER_EXPERIENCE_TICK,
                'experience_rate_label' => 'Crafts 6 alchemy items per minute.',
            ];
        }

        if ($type === BatchCraftingType::TRINKETRY) {
            return [
                'actions_per_minute' => self::ITEMS_PER_EXPERIENCE_TICK,
                'experience_rate_label' => 'Crafts 6 trinkets per minute.',
            ];
        }

        return [
            'actions_per_minute' => null,
            'experience_rate_label' => null,
        ];
    }

    private function goalRemaining(GlobalEventGoal $goal): int
    {
        if (! is_null($goal->max_crafts)) {
            return max(0, $goal->max_crafts - $goal->total_crafts);
        }

        if (! is_null($goal->max_enchants)) {
            return max(0, $goal->max_enchants - $goal->total_enchants);
        }

        if (! is_null($goal->max_kills)) {
            return max(0, $goal->max_kills - $goal->total_kills);
        }

        return 0;
    }

    private function eventGoalProgress(array $progress): ?array
    {
        $goalId = $progress['event_goal_id'] ?? null;

        if (is_null($goalId)) {
            return null;
        }

        $goal = GlobalEventGoal::find($goalId);

        if (is_null($goal)) {
            return null;
        }

        if (($progress['event_action'] ?? null) === 'craft') {
            return [
                'current' => $goal->total_crafts,
                'max' => $goal->max_crafts,
            ];
        }

        if (($progress['event_action'] ?? null) === 'enchant') {
            return [
                'current' => $goal->total_enchants,
                'max' => $goal->max_enchants,
            ];
        }

        return null;
    }

    private function eventCharacterContribution(Character $character, array $progress): ?array
    {
        $goalId = $progress['event_goal_id'] ?? null;

        if (is_null($goalId)) {
            return null;
        }

        $goal = GlobalEventGoal::find($goalId);

        if (is_null($goal)) {
            return null;
        }

        if (($progress['event_action'] ?? null) === 'craft') {
            $current = $character->globalEventCrafts()->where('global_event_goal_id', $goal->id)->first()?->crafts ?? 0;
        } elseif (($progress['event_action'] ?? null) === 'enchant') {
            $current = $character->globalEventEnchants()->where('global_event_goal_id', $goal->id)->first()?->enchants ?? 0;
        } else {
            $current = 0;
        }

        return [
            'current' => $current,
            'reward_threshold' => $this->eventGoalsServiceAmountNeeded($goal),
        ];
    }

    private function eventGoalsServiceAmountNeeded(GlobalEventGoal $goal): int
    {
        $participants = $goal->globalEventParticipation()->count();

        if ($participants > 0) {
            return (int) round($goal->reward_every / $participants);
        }

        return (int) $goal->reward_every;
    }

    private function eventGoalCompletedAfterTick(BatchCrafting $batchCrafting): bool
    {
        $progress = $batchCrafting->progress ?? [];

        if (($progress['event_mode'] ?? false) !== true) {
            return false;
        }

        $goal = GlobalEventGoal::find($progress['event_goal_id'] ?? null);

        if (is_null($goal)) {
            return false;
        }

        if (($progress['event_action'] ?? null) === 'craft') {
            return ! is_null($goal->max_crafts) && $goal->total_crafts >= $goal->max_crafts;
        }

        if (($progress['event_action'] ?? null) === 'enchant') {
            return ! is_null($goal->max_enchants) && $goal->total_enchants >= $goal->max_enchants;
        }

        return false;
    }

    private function cycleOrEndEventGoal(BatchCrafting $batchCrafting, Character $character): ?BatchCraftingEndReason
    {
        $progress = $batchCrafting->progress ?? [];
        $action = $progress['event_action'] ?? null;
        $completedGoal = GlobalEventGoal::find($progress['event_goal_id'] ?? null);

        if (! is_null($completedGoal)) {
            $this->globalEventGoalProgressionService->advanceIfCurrentGoalComplete($completedGoal);
        }

        $nextGoal = $action === 'enchant'
            ? $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character)
            : $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($nextGoal)) {
            return BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
        }

        $progress['event_goal_id'] = $nextGoal->id;
        $batchCrafting->update(['progress' => $progress]);
        $this->logger()->eventGoalCycled($batchCrafting, $completedGoal?->id, $nextGoal->id);

        return null;
    }

    private function exceptionLogContext(BatchCrafting $batchCrafting, Character $character): array
    {
        $progress = $batchCrafting->progress ?? [];
        $lastAction = collect($batchCrafting->action_log ?? [])->last();
        $eventQueue = $progress['event_craft_queue'] ?? self::eventCraftQueue();
        $eventIndex = max(0, (int) ($progress['event_craft_index'] ?? 0) - 1);

        return [
            'batch_crafting_id' => $batchCrafting->id,
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => $batchCrafting->batch_type,
            'mode' => $progress['craft_mode'] ?? $progress['alchemy_mode'] ?? $progress['trinketry_mode'] ?? $progress['enchant_mode'] ?? $progress['holy_oil_mode'] ?? null,
            'progress' => $progress,
            'last_action' => $lastAction,
            'phase' => $progress['event_enchant_phase']
                ?? $progress['event_fallback_phase']
                ?? $progress['craft_enchant_set_phase']
                ?? null,
            'target' => $eventQueue[$eventIndex] ?? null,
            'action' => $lastAction['action_type'] ?? $lastAction['action'] ?? null,
            'action_index' => $progress['event_craft_index']
                ?? $progress['craft_experience_index']
                ?? $progress['craft_set_index']
                ?? count($batchCrafting->action_log ?? []),
            'selected_item_id' => $progress['specific_item_id'] ?? $progress['alchemy_item_id'] ?? null,
            'selected_affix_ids' => $progress['enchant_affix_ids'] ?? null,
            'event_type' => $progress['event_type'] ?? null,
            'event_goal_id' => $progress['event_goal_id'] ?? null,
        ];
    }

    private function reportBatchCraftingException(BatchCrafting $batchCrafting, Character $character, Throwable $throwable): void
    {
        $this->monitoredBugReportService->reportError(
            'batch_crafting',
            $throwable->getMessage(),
            $this->exceptionLogContext($batchCrafting, $character),
            get_class($throwable),
            $character->id,
            (string) $batchCrafting->id,
        );
    }

    private function validatedProgress(Character $character, BatchCraftingType $type, array $data): array
    {
        $progress = $data['progress'] ?? [];
        $disposition = BatchCraftingDisposition::from($data['disposition']);

        if (! $this->supportsSelectableOutputDestination($type, $progress, $disposition)) {
            unset($progress['output_destination'], $progress['output_set_id']);
        }

        if (in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true)) {
            $mode = $progress['craft_mode'] ?? 'experience';

            if ($mode === 'event') {
                if ($type !== BatchCraftingType::CRAFT) {
                    throw ValidationException::withMessages([
                        'progress.craft_mode' => 'Event crafting is only available for craft batches.',
                    ]);
                }

                return $this->eventCraftProgress($character);
            }

            if ($mode === 'experience') {
                if ($type === BatchCraftingType::CRAFT) {
                    if ($this->areAllCraftingSkillsMaxed($character)) {
                        throw ValidationException::withMessages([
                            'progress.craft_mode' => 'Your Weapon Crafting, Armour Crafting, Ring Crafting, and Spell Crafting are all maxed. You cannot craft for experience. You can craft an amount or a set if you would like.',
                        ]);
                    }

                    unset($progress['craft_experience_skill']);
                }

                if ($type === BatchCraftingType::CRAFT_AND_ENCHANT) {
                    if ($this->areAllCraftingSkillsMaxed($character) && $this->isEnchantingMaxed($character)) {
                        throw ValidationException::withMessages([
                            'progress.craft_mode' => 'Weapon Crafting, Armour Crafting, Ring Crafting, Spell Crafting, and Enchanting are all maxed, so this character cannot batch craft and enchant for experience.',
                        ]);
                    }

                    unset($progress['craft_experience_skill']);
                }
            }

            if ($mode === 'specific_item') {
                $this->validateSpecificCraftItem($character, $progress);
                $progress = $this->normalizeOutputDestination($type, $progress, $disposition);
            }

            if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && $mode === 'specific_item') {
                $this->validateEnchantAffixes($character, $progress['enchant_affix_ids'] ?? []);
            }

            if ($type === BatchCraftingType::CRAFT && $mode === 'craft_set') {
                return $this->normalizeOutputDestination($type, $this->craftSetProgress($character, $progress), $disposition);
            }

            if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && $mode === 'craft_enchant_set') {
                return $this->normalizeOutputDestination($type, $this->craftEnchantSetProgress($character, $progress), $disposition);
            }
        }

        if ($type === BatchCraftingType::ENCHANT) {
            $mode = $progress['enchant_mode'] ?? 'event';

            if ($mode === 'set') {
                throw ValidationException::withMessages([
                    'progress.enchant_mode' => 'Standalone Enchant Set is no longer available. Use Enchant For Event.',
                ]);
            }

            return $this->eventEnchantProgress($character);
        }

        if ($type === BatchCraftingType::ALCHEMY) {
            $mode = $progress['alchemy_mode'] ?? 'experience';
            $progress['alchemy_mode'] = $mode;

            if ($mode === 'experience') {
                $this->rejectMaxedSkill($character, 'Alchemy', 'progress.alchemy_mode');
            }

            if ($mode === 'amount') {
                $progress['alchemy_amount'] = (int) ($progress['alchemy_amount'] ?? $progress['craft_amount'] ?? 0);
                $progress['alchemy_item_id'] = (int) ($progress['alchemy_item_id'] ?? 0);
            }
        }

        if ($type === BatchCraftingType::TRINKETRY) {
            $progress['trinketry_mode'] = 'experience';
            $this->rejectMaxedSkill($character, 'Trinketry', 'progress.trinketry_mode');
        }

        if ($type === BatchCraftingType::HOLY_OILS) {
            $mode = $progress['holy_oil_mode'] ?? 'selected';
            $progress['holy_oil_mode'] = $mode;

            if ($mode === 'set') {
                return array_merge($progress, $this->holyOilSetProgress($character, $progress));
            }
        }

        return $progress;
    }

    /**
     * Normalizes progress.output_destination/progress.output_set_id for finite KEEP
     * batches that retain every final item (Craft Amount, Craft Set, Craft and Enchant
     * Amount, Craft and Enchant Set). Backward-compatible: missing/older batches default
     * to crafted_items_set, matching pre-existing behavior.
     */
    private function normalizeOutputDestination(BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): array
    {
        if (! $this->supportsSelectableOutputDestination($type, $progress, $disposition)) {
            unset($progress['output_destination'], $progress['output_set_id']);

            return $progress;
        }

        $progress['output_destination'] = $progress['output_destination'] ?? 'crafted_items_set';

        if ($progress['output_destination'] === 'inventory_set') {
            $progress['output_set_id'] = isset($progress['output_set_id']) ? (int) $progress['output_set_id'] : null;
        } else {
            unset($progress['output_set_id']);
        }

        return $progress;
    }

    /**
     * True only for the four finite KEEP modes that retain every final item and
     * offer the selectable output destination (Craft Amount, Craft Set, Craft and
     * Enchant Amount, Craft and Enchant Set). Every other type/mode/disposition
     * combination keeps its existing non-selectable Crafted Items Set destination
     * and must never retain a manually supplied output_destination/output_set_id.
     */
    private function supportsSelectableOutputDestination(BatchCraftingType $type, array $progress, BatchCraftingDisposition $disposition): bool
    {
        if ($disposition !== BatchCraftingDisposition::KEEP) {
            return false;
        }

        return $this->isSelectableOutputDestinationMode($type, $progress);
    }

    /**
     * Read-only resolution of the currently selected output destination, defaulting to
     * crafted_items_set for older/backward-compatible batches with no stored value.
     */
    private function resolvedOutputDestination(array $progress): string
    {
        return $progress['output_destination'] ?? 'crafted_items_set';
    }

    /**
     * Player-facing label for the resolved output destination: the exact selected set
     * name, "Inventory", or "Crafted Items Set" (default/backward-compatible).
     */
    private function outputDestinationLabel(Character $character, array $progress): string
    {
        $destination = $this->resolvedOutputDestination($progress);

        if ($destination === 'inventory') {
            return 'Inventory';
        }

        if ($destination === 'inventory_set') {
            $set = InventorySet::where('id', $progress['output_set_id'] ?? 0)->where('character_id', $character->id)->first();

            return is_null($set) ? 'Inventory Set' : $this->inventorySetDisplayName($character, $set);
        }

        return 'Crafted Items Set';
    }

    private function inventorySetDisplayName(Character $character, InventorySet $inventorySet): string
    {
        if (! is_null($inventorySet->name)) {
            return $inventorySet->name;
        }

        $position = $character->inventorySets()
            ->where('id', '<=', $inventorySet->id)
            ->where(function ($query): void {
                $query->whereNull('special_type')
                    ->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE);
            })
            ->count();

        return 'Set '.$position;
    }

    private function craftSetProgress(Character $character, array $progress): array
    {
        $plan = is_array($progress['craft_set_plan'] ?? null) ? $progress['craft_set_plan'] : [];
        $plannerTargets = $this->processor->craftSetPlannerTargets();
        $plannerKeys = $this->processor->craftEnchantSetPlanKeys($plannerTargets);

        $this->validateCraftSetPlan($character, $plannerTargets, $plannerKeys, $plan);

        $queue = $this->processor->craftSetExecutionQueue($character, $plan);
        $keys = $this->processor->craftEnchantSetPlanKeys($queue);

        return [
            'craft_mode' => 'craft_set',
            'output_destination' => $progress['output_destination'] ?? null,
            'output_set_id' => $progress['output_set_id'] ?? null,
            'craft_set_queue' => $queue,
            'craft_set_keys' => $keys,
            'craft_set_plan' => $plan,
            'craft_set_selected_item_ids' => $this->resolveSetPlanSelectedItemIds($character, $queue, $keys, $plan),
            'craft_set_index' => 0,
            'craft_set_requested' => count($queue),
            'craft_set_completed' => 0,
        ];
    }

    private function validateCraftSetPlan(Character $character, array $queue, array $keys, array $plan): void
    {
        $selectedHands = collect();

        foreach ($keys as $index => $key) {
            $entry = is_array($plan[$key] ?? null) ? $plan[$key] : [];
            $target = $queue[$index];
            $isHand = ($target['category'] ?? null) === 'hand';
            $candidates = $isHand
                ? $this->processor->craftableHandCandidates($character)
                : $this->processor->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type']);

            if (! isset($entry['selected_item_id']) || is_null($entry['selected_item_id'])) {
                if (! ($target['optional'] ?? false) && $candidates->isEmpty()) {
                    throw ValidationException::withMessages([
                        'progress.craft_set_plan' => 'No craftable item is currently available for '.$target['label'].'.',
                    ]);
                }

                continue;
            }

            $selectedItemId = (int) $entry['selected_item_id'];
            $selectedItem = $candidates->first(fn ($candidate) => (int) $candidate->id === $selectedItemId);

            if (is_null($selectedItem)) {
                throw ValidationException::withMessages([
                    'progress.craft_set_plan' => 'One or more selected items are not craftable by this character for the matching slot.',
                ]);
            }

            if ($isHand) {
                if (! $this->setHandsValidation->isHandItem($selectedItem)) {
                    throw ValidationException::withMessages([
                        'progress.craft_set_plan' => 'Only weapons and Shields may be selected for Left Hand or Right Hand.',
                    ]);
                }

                $selectedHands->push($selectedItem);
            }
        }

        if (! $this->setHandsValidation->areHandItemsValid($selectedHands)) {
            throw ValidationException::withMessages([
                'progress.craft_set_plan' => 'A two-handed weapon occupies both hands. Clear the other hand before starting.',
            ]);
        }
    }

    private function craftEnchantSetProgress(Character $character, array $progress): array
    {
        $plan = is_array($progress['enchant_plan'] ?? null) ? $progress['enchant_plan'] : [];
        $plannerTargets = $this->processor->craftSetPlannerTargets();
        $plannerKeys = $this->processor->craftEnchantSetPlanKeys($plannerTargets);

        $this->validateCraftSetPlan($character, $plannerTargets, $plannerKeys, $plan);

        $queue = $this->processor->craftSetExecutionQueue($character, $plan);
        $keys = $this->processor->craftEnchantSetPlanKeys($queue);

        $this->validateCraftEnchantSetPlan($character, $queue, $keys, $plan);

        return [
            'craft_mode' => 'craft_enchant_set',
            'craft_enchant_set_target_mode' => 'craft_new',
            'output_destination' => $progress['output_destination'] ?? null,
            'output_set_id' => $progress['output_set_id'] ?? null,
            'craft_enchant_set_queue' => $queue,
            'craft_enchant_set_keys' => $keys,
            'enchant_plan' => $plan,
            'craft_enchant_set_selected_item_ids' => $this->resolveSetPlanSelectedItemIds($character, $queue, $keys, $plan),
            'craft_enchant_set_requested' => count($queue),
            'craft_enchant_set_phase' => 'crafting',
            'craft_enchant_set_craft_index' => 0,
            'craft_enchant_set_enchant_index' => 0,
            'craft_enchant_set_crafted_item_ids' => [],
            'craft_enchant_set_prefix_applied_count' => 0,
            'craft_enchant_set_suffix_applied_count' => 0,
            'craft_enchant_set_total_work_units' => count($queue),
            'craft_enchant_set_completed_work_units' => 0,
            'craft_enchant_set_surviving_crafted_count' => 0,
            'craft_enchant_set_replacement_key' => null,
            'craft_enchant_set_finalized_keys' => [],
            'craft_enchant_set_lost_item_keys' => [],
            'craft_enchant_set_counted_crafted_keys' => [],
        ];
    }

    /**
     * Enchant an already-built, valid full 23-item set in place. This is a distinct
     * mode from the default build-new-set flow above: it operates on items the
     * character already owns in a real selected set and never writes to the
     * Crafted Items Set, so a target set is still required here.
     */
    private function craftEnchantSetEnchantExistingProgress(Character $character, array $progress): array
    {
        $set = $this->validateOwnedInventorySet($character, (int) ($progress['selected_set_id'] ?? 0), 'progress.selected_set_id');

        if ($set->is_equipped) {
            throw ValidationException::withMessages([
                'progress.selected_set_id' => 'Equipped sets cannot be used for this batch. Unequip the set or choose another set.',
            ]);
        }

        $queue = $this->processor->craftSetQueue();
        $keys = $this->processor->craftEnchantSetPlanKeys($queue);
        $plan = is_array($progress['enchant_plan'] ?? null) ? $progress['enchant_plan'] : [];

        $this->validateCraftEnchantSetPlan($character, $queue, $keys, $plan);

        $composition = $this->validateEnchantableSetComposition($set);

        if (! $composition['valid']) {
            throw ValidationException::withMessages([
                'progress.selected_set_id' => $composition['message'],
            ]);
        }

        $existingSlotIds = $this->mapExistingSetSlotsToPlanKeys($set, $keys);

        return [
            'craft_mode' => 'craft_enchant_set',
            'selected_set_id' => $set->id,
            'craft_enchant_set_target_mode' => 'enchant_existing',
            'craft_enchant_set_queue' => $queue,
            'craft_enchant_set_keys' => $keys,
            'enchant_plan' => $plan,
            'craft_enchant_set_existing_slot_ids' => $existingSlotIds,
            'craft_enchant_set_requested' => count($queue),
            'craft_enchant_set_phase' => 'enchanting',
            'craft_enchant_set_enchant_index' => 0,
            'craft_enchant_set_prefix_applied_count' => 0,
            'craft_enchant_set_suffix_applied_count' => 0,
            'craft_enchant_set_total_work_units' => count($queue),
            'craft_enchant_set_completed_work_units' => 0,
        ];
    }

    private function mapExistingSetSlotsToPlanKeys(InventorySet $set, array $keys): array
    {
        $slotsByType = $set->slots()->with('item')->get()->groupBy(fn (SetSlot $slot) => $slot->item?->type);
        $mapped = [];

        foreach ($keys as $key) {
            $type = str_starts_with($key, 'ring_') ? ItemType::RING->value : $key;

            if (! isset($slotsByType[$type]) || $slotsByType[$type]->isEmpty()) {
                continue;
            }

            $mapped[$key] = $slotsByType[$type]->shift()->id;
        }

        return $mapped;
    }

    private function validateCraftEnchantSetPlan(Character $character, array $queue, array $keys, array $plan): void
    {
        $missingKeys = array_values(array_diff($keys, array_keys($plan)));

        if (! empty($missingKeys)) {
            throw ValidationException::withMessages([
                'progress.enchant_plan' => 'Every item in the full set must have a prefix or a suffix selected.',
            ]);
        }

        $prefixIds = [];
        $suffixIds = [];

        foreach ($keys as $index => $key) {
            $entry = is_array($plan[$key] ?? null) ? $plan[$key] : [];
            $prefixId = isset($entry['prefix_affix_id']) ? (int) $entry['prefix_affix_id'] : null;
            $suffixId = isset($entry['suffix_affix_id']) ? (int) $entry['suffix_affix_id'] : null;

            if (is_null($prefixId) && is_null($suffixId)) {
                throw ValidationException::withMessages([
                    'progress.enchant_plan' => 'Every item in the full set must have a prefix or a suffix selected.',
                ]);
            }

            if (! is_null($prefixId)) {
                $prefixIds[] = $prefixId;
            }

            if (! is_null($suffixId)) {
                $suffixIds[] = $suffixId;
            }

            if (isset($entry['selected_item_id']) && ! is_null($entry['selected_item_id'])) {
                $target = $queue[$index];
                $candidates = $this->processor->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type']);
                $selectedItemId = (int) $entry['selected_item_id'];

                if (! $candidates->contains(fn ($candidate) => (int) $candidate->id === $selectedItemId)) {
                    throw ValidationException::withMessages([
                        'progress.enchant_plan' => 'One or more selected items are not craftable by this character for the matching slot.',
                    ]);
                }
            }
        }

        $prefixAffixes = ItemAffix::whereIn('id', $prefixIds)->get();
        $suffixAffixes = ItemAffix::whereIn('id', $suffixIds)->get();

        if ($prefixAffixes->count() !== count(array_unique($prefixIds)) || $prefixAffixes->contains(fn (ItemAffix $affix) => $affix->type !== 'prefix')) {
            throw ValidationException::withMessages([
                'progress.enchant_plan' => 'One or more selected prefix enchantments are invalid.',
            ]);
        }

        if ($suffixAffixes->count() !== count(array_unique($suffixIds)) || $suffixAffixes->contains(fn (ItemAffix $affix) => $affix->type !== 'suffix')) {
            throw ValidationException::withMessages([
                'progress.enchant_plan' => 'One or more selected suffix enchantments are invalid.',
            ]);
        }

        $this->validateAffixesAvailableByEnchantingLevel($character, $prefixAffixes->merge($suffixAffixes));
    }

    private function resolveSetPlanSelectedItemIds(Character $character, array $queue, array $keys, array $plan): array
    {
        $resolvedItemIds = [];

        foreach ($keys as $index => $key) {
            $target = $queue[$index];
            $entry = is_array($plan[$key] ?? null) ? $plan[$key] : [];
            $candidates = $this->processor->craftableItemCandidatesForTarget($character, $target['type'], $target['crafting_type']);
            $selectedItemId = isset($entry['selected_item_id']) ? (int) $entry['selected_item_id'] : null;
            $selectedCandidate = is_null($selectedItemId)
                ? null
                : $candidates->first(fn ($candidate) => (int) $candidate->id === $selectedItemId);
            $resolvedId = $selectedCandidate?->id ?? $candidates->first()?->id;

            if (! is_null($resolvedId)) {
                $resolvedItemIds[$key] = (int) $resolvedId;
            }
        }

        return $resolvedItemIds;
    }

    private function enchantSetProgress(Character $character, array $progress): array
    {
        $set = $this->validateOwnedInventorySet($character, (int) ($progress['selected_set_id'] ?? 0), 'progress.selected_set_id');
        $this->validateEnchantAffixes($character, $progress['enchant_affix_ids'] ?? []);
        $eligibleTotal = $set->slots()->with('item')->get()
            ->filter(fn ($slot) => $this->processor->isEnchantableSetItem($slot->item))
            ->count();

        if ($eligibleTotal < 1) {
            throw ValidationException::withMessages([
                'progress.selected_set_id' => 'The selected set has no items eligible for enchanting.',
            ]);
        }

        return [
            'enchant_mode' => 'set',
            'selected_set_id' => $set->id,
            'enchant_affix_ids' => array_values($progress['enchant_affix_ids']),
            'enchant_set_total' => $eligibleTotal,
            'enchant_set_completed' => 0,
            'enchant_set_skipped' => 0,
        ];
    }

    private function holyOilSetProgress(Character $character, array $progress): array
    {
        $set = $this->validateOwnedInventorySet($character, (int) ($progress['selected_set_id'] ?? 0), 'progress.selected_set_id');

        $eligibleSlots = $set->slots()->with('item')->get()
            ->filter(fn ($slot) => ! is_null($slot->item) && ! in_array($slot->item->type, ['trinket', 'artifact'], true))
            ->filter(fn ($slot) => ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0);
        $totalRemainingStacks = $eligibleSlots->sum(fn ($slot) => max(0, $slot->item->holy_stacks - $slot->item->holy_stacks_applied));

        return [
            'holy_oil_mode' => 'set',
            'selected_set_id' => $set->id,
            'holy_oil_eligible_items' => $eligibleSlots->count(),
            'holy_oil_total_stacks' => $totalRemainingStacks,
            'holy_oil_requested_applications' => $totalRemainingStacks,
            'holy_oil_completed_applications' => 0,
        ];
    }

    private function validateOwnedInventorySet(Character $character, int $setId, string $field): InventorySet
    {
        $set = InventorySet::where('id', $setId)->where('character_id', $character->id)->first();

        if (is_null($set) || $set->isBatchCraftingSet()) {
            throw ValidationException::withMessages([
                $field => 'The selected set does not belong to this character.',
            ]);
        }

        return $set;
    }

    private function eventCraftProgress(Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($goal)) {
            throw ValidationException::withMessages([
                'progress.craft_mode' => 'Event crafting is not available for this character.',
            ]);
        }

        return [
            'event_mode' => true,
            'event_action' => 'craft',
            'event_type' => $goal->event_type,
            'event_id' => $goal->event_id,
            'event_goal_id' => $goal->id,
            'event_step' => GlobalEventSteps::CRAFT,
            'craft_mode' => 'event',
            'tick_delay_seconds' => self::RECURRING_DELAY_SECONDS,
            'event_actions_per_tick' => self::EVENT_ITEMS_PER_SET_TICK,
            'event_craft_queue' => self::eventCraftQueue(),
            'event_craft_index' => 0,
        ];
    }

    private function eventEnchantProgress(Character $character): array
    {
        $goal = $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);

        if (is_null($goal)) {
            throw ValidationException::withMessages([
                'batch_type' => 'Event enchanting is not available for this character.',
            ]);
        }

        return [
            'event_mode' => true,
            'event_action' => 'enchant',
            'event_type' => $goal->event_type,
            'event_id' => $goal->event_id,
            'event_goal_id' => $goal->id,
            'event_step' => GlobalEventSteps::ENCHANT,
            'enchant_mode' => 'event',
            'event_enchant_phase' => 'enchant_event_inventory',
            'event_fallback_phase' => null,
            'event_fallback_event_slot_ids' => [],
            'tick_delay_seconds' => self::RECURRING_DELAY_SECONDS,
            'event_actions_per_tick' => self::EVENT_ITEMS_PER_SET_TICK,
        ];
    }

    private function validateSpecificCraftItem(Character $character, array $progress): void
    {
        if (is_null($this->craftingService)) {
            return;
        }

        $craftingType = $progress['specific_crafting_type'] ?? null;
        $itemId = (int) ($progress['specific_item_id'] ?? 0);

        if (is_null($craftingType) || $itemId <= 0) {
            throw ValidationException::withMessages([
                'progress.specific_item_id' => 'Select a valid item to craft.',
            ]);
        }

        $craftableItems = $this->specificCraftableItems($character, $craftingType);

        if ($craftableItems->first(fn ($item) => (int) $item->id === $itemId) === null) {
            throw ValidationException::withMessages([
                'progress.specific_item_id' => 'The selected item is not craftable by this character.',
            ]);
        }
    }

    private function specificCraftableItems(Character $character, string $craftingType)
    {
        return $this->craftingService->fetchCraftableItems($character, [
            'crafting_type' => $craftingType,
        ], false);
    }

    private function validateEnchantAffixes(Character $character, array $affixIds): void
    {
        if (empty($affixIds)) {
            throw ValidationException::withMessages([
                'progress.enchant_affix_ids' => 'Select at least one enchantment.',
            ]);
        }

        $affixes = ItemAffix::whereIn('id', $affixIds)->get();

        if ($affixes->count() !== count($affixIds)) {
            throw ValidationException::withMessages([
                'progress.enchant_affix_ids' => 'One or more selected enchantments are invalid.',
            ]);
        }

        $this->validateAffixesAvailableByEnchantingLevel($character, $affixes);
    }

    private function validateAffixesAvailableByEnchantingLevel(Character $character, Collection $affixes): void
    {
        $enchantingLevel = $this->enchantingSkillLevel($character);

        if ($affixes->contains(fn (ItemAffix $affix) => $affix->skill_level_required > $enchantingLevel)) {
            throw ValidationException::withMessages([
                'progress.enchant_affix_ids' => 'One or more selected enchantments are above the current Enchanting level.',
            ]);
        }
    }

    private function enchantingSkillLevel(Character $character): int
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->first();

        return $skill?->level ?? 0;
    }

    private function rejectMaxedSkill(Character $character, string $skillName, string $field): void
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('name', $skillName))
            ->with('baseSkill')
            ->first();

        if (! is_null($skill) && $skill->level >= $skill->max_level) {
            throw ValidationException::withMessages([
                $field => $skillName.' is already maxed.',
            ]);
        }
    }

    private function areAllCraftingSkillsMaxed(Character $character): bool
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

    private function isSkillMaxedByName(Character $character, string $skillName): bool
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('name', $skillName))
            ->with('baseSkill')
            ->first();

        return ! is_null($skill) && $skill->level >= $skill->max_level;
    }

    private function isEnchantingMaxed(Character $character): bool
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('type', SkillTypeValue::ENCHANTING->value))
            ->with('baseSkill')
            ->first();

        return ! is_null($skill) && $skill->level >= $skill->max_level;
    }

    private function craftingSkillName(string $craftingType): string
    {
        if (in_array($craftingType, ['dagger', 'sword', 'claw', 'wand', 'censer', 'stave', 'hammer', 'bow', 'gun', 'fan', 'mace', 'scratch-awl', 'weapon'], true)) {
            return 'Weapon Crafting';
        }

        return match ($craftingType) {
            'ring' => 'Ring Crafting',
            'spell', 'spell-damage', 'spell-healing', 'spell_damage', 'spell_healing' => 'Spell Crafting',
            default => 'Armour Crafting',
        };
    }

    private function validateHolyOilSelections(Character $character, array $data): void
    {
        $mode = $data['progress']['holy_oil_mode'] ?? 'selected';
        $selectedOils = $data['selected_oils'] ?? [];

        if (empty($selectedOils)) {
            throw ValidationException::withMessages([
                'selected_oils' => 'Select at least one usable Holy Oil.',
            ]);
        }

        $this->validateHolyOilOilSelections($character, $selectedOils);

        if ($mode === 'set') {
            return;
        }

        $selectedItems = $data['selected_items'] ?? [];

        if (empty($selectedItems)) {
            throw ValidationException::withMessages([
                'selected_items' => 'Select at least one eligible item for Holy Oils.',
            ]);
        }

        foreach ($selectedItems as $slotId) {
            $slot = $character->inventory?->slots()
                ->where('id', $slotId)
                ->with('item.appliedHolyStacks')
                ->first();

            if (is_null($slot) || is_null($slot->item)) {
                throw ValidationException::withMessages([
                    'selected_items' => 'One or more selected items do not belong to this character.',
                ]);
            }

            if (in_array($slot->item->type, ['trinket', 'artifact']) || ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) <= 0) {
                throw ValidationException::withMessages([
                    'selected_items' => 'One or more selected items cannot receive Holy Oils.',
                ]);
            }
        }
    }

    private function holyOilDispositionEligible(Character $character, array $data, BatchCraftingDisposition $disposition): bool
    {
        $mode = $data['progress']['holy_oil_mode'] ?? 'selected';

        if ($mode === 'set') {
            $set = InventorySet::where('id', $data['progress']['selected_set_id'] ?? 0)
                ->where('character_id', $character->id)
                ->first();

            if (is_null($set)) {
                return false;
            }

            $enchantedStates = $set->slots()
                ->whereHas('item', fn ($query) => $query->whereNotIn('type', ['trinket', 'artifact']))
                ->with('item')
                ->get()
                ->filter(fn (SetSlot $slot) => ! is_null($slot->item) && ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0)
                ->map(fn (SetSlot $slot) => ! is_null($slot->item->item_prefix_id) || ! is_null($slot->item->item_suffix_id));

            if ($enchantedStates->isEmpty()) {
                return false;
            }

            return $enchantedStates->every(fn (bool $hasEnchant) => $hasEnchant);
        }

        $selectedItems = $data['selected_items'] ?? [];

        if (empty($selectedItems) || is_null($character->inventory)) {
            return false;
        }

        $enchantedStates = $character->inventory->slots()
            ->whereIn('id', $selectedItems)
            ->with('item')
            ->get()
            ->filter(fn ($slot) => ! is_null($slot->item)
                && ! in_array($slot->item->type, ['trinket', 'artifact'], true)
                && ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0)
            ->map(fn ($slot) => ! is_null($slot->item->item_prefix_id) || ! is_null($slot->item->item_suffix_id));

        if ($enchantedStates->count() !== count($selectedItems) || $enchantedStates->isEmpty()) {
            return false;
        }

        return $enchantedStates->every(fn (bool $hasEnchant) => $hasEnchant);
    }

    private function validateHolyOilOilSelections(Character $character, array $selectedOils): void
    {
        foreach ($selectedOils as $oilSlotId) {
            $oilSlot = AlchemyBagSlot::where('id', $oilSlotId)
                ->where('character_id', $character->id)
                ->with('item')
                ->first();

            if (is_null($oilSlot) || is_null($oilSlot->item)) {
                throw ValidationException::withMessages([
                    'selected_oils' => 'One or more selected oils do not belong to this character.',
                ]);
            }

            if ($oilSlot->amount <= 0 || ! $oilSlot->item->can_use_on_other_items || is_null($oilSlot->item->holy_level)) {
                throw ValidationException::withMessages([
                    'selected_oils' => 'One or more selected oils cannot be used as Holy Oils.',
                ]);
            }
        }
    }
}
