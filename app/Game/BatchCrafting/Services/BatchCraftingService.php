<?php

namespace App\Game\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\AutomationType;
use App\Game\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Validation\ValidationException;

class BatchCraftingService
{
    public const DURATION_HOURS = 8;

    public const SETS_PER_RECURRING_TICK = 6;

    public const ITEMS_PER_RECURRING_TICK = 6;

    public const RECURRING_DELAY_SECONDS = 60;

    public const IMMEDIATE_DELAY_SECONDS = 2;

    public function __construct(
        private readonly BatchCraftingProcessor $processor,
        private readonly CraftingService $craftingService,
        private readonly BatchCraftingLogger $batchCraftingLogger,
    ) {}

    public function start(Character $character, array $data): BatchCrafting
    {
        $type = BatchCraftingType::from($data['batch_type']);
        $disposition = BatchCraftingDisposition::from($data['disposition']);

        if (! $disposition->isAllowedFor($type)) {
            throw ValidationException::withMessages([
                'disposition' => 'This disposition is not allowed for this batch crafting type.',
            ]);
        }

        if ($this->active($character)) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting is already running for this character.',
            ]);
        }

        if ($character->currentAutomations()->where('type', AutomationType::FACTION_LOYALTY)->exists()) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start while faction loyalty automation is running.',
            ]);
        }

        if ($character->is_dead) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start while the character is dead.',
            ]);
        }

        if ($this->isInventoryFull($character)) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start without inventory space.',
            ]);
        }

        if ($this->currencyAmount($character, $type->requiredCurrency()) <= 0) {
            throw ValidationException::withMessages([
                'batch_crafting' => 'Batch crafting cannot start without the required currency.',
            ]);
        }

        if ($type === BatchCraftingType::HOLY_OILS) {
            $this->validateHolyOilSelections($character, $data);
        }

        $progress = $this->validatedProgress($character, $type, $data);
        $progress['tick_delay_seconds'] = $this->tickDelaySeconds($type, $progress);

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

        event(new ServerMessageEvent($character->user, 'Batch crafting has started. Type: ' . $type->label()));
        event(new BatchCraftingStatusUpdated($character->user_id));
        event(new BatchCraftingMonitoringUpdated($character->id));

        if (! app()->runningUnitTests()) {
            BatchCraftingJob::dispatch($batchCrafting->id)->delay(now()->addSeconds($progress['tick_delay_seconds']))->onConnection('long_running')->onQueue('default_long');
        }

        return $batchCrafting;
    }

    public function active(Character $character): ?BatchCrafting
    {
        return BatchCrafting::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    public function visible(Character $character): ?BatchCrafting
    {
        return BatchCrafting::where('character_id', $character->id)
            ->where(function ($query) {
                $query->whereNull('completed_at')
                    ->whereNull('cancelled_at')
                    ->orWhereNull('panel_dismissed_at');
            })
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    public function status(Character $character): array
    {
        $batchCrafting = $this->visible($character);

        if (is_null($batchCrafting)) {
            return [
                'active' => false,
                'completed' => false,
                'show_info' => ! BatchCrafting::where('character_id', $character->id)->where('info_acknowledged', true)->exists(),
            ];
        }

        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $timer = $this->timerDetails($batchCrafting);
        $actionLog = $batchCrafting->action_log ?? [];
        $progress = $batchCrafting->progress ?? [];
        $batchCraftingSet = InventorySet::query()
            ->where('character_id', $character->id)
            ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
            ->first();
        $progressPercent = $this->progressPercent($batchCrafting, $timer['progress_percent']);

        return [
            'active' => $batchCrafting->isRunning(),
            'completed' => ! $batchCrafting->isRunning(),
            'show_info' => ! BatchCrafting::where('character_id', $character->id)->where('info_acknowledged', true)->exists(),
            'batch' => [
                'id' => $batchCrafting->id,
                'batch_type' => $batchCrafting->batch_type,
                'batch_label' => $type->label(),
                'disposition' => $batchCrafting->disposition,
                'started_at' => $batchCrafting->started_at?->toJSON(),
                'ends_at' => $batchCrafting->ends_at?->toJSON(),
                'completed_at' => $batchCrafting->completed_at?->toJSON(),
                'elapsed_seconds' => $timer['elapsed_seconds'],
                'remaining_seconds' => $timer['remaining_seconds'],
                'elapsed_human' => $timer['elapsed_human'],
                'remaining_human' => $timer['remaining_human'],
                'progress_percent' => $progressPercent,
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
                'mode' => $progress['craft_mode'] ?? $progress['alchemy_mode'] ?? $progress['trinketry_mode'] ?? null,
                'phase' => $progress['craft_enchant_phase'] ?? null,
                'next_action' => $this->nextAction($batchCrafting),
                'requested_amount' => $progress['craft_amount'] ?? $progress['alchemy_amount'] ?? $progress['holy_oil_requested_applications'] ?? null,
                'completed_amount' => $progress['craft_specific_count'] ?? $progress['craft_enchant_specific_count'] ?? $progress['alchemy_amount_count'] ?? $progress['holy_oil_completed_applications'] ?? null,
                'no_inventory_reason' => $progress['no_inventory_reason'] ?? null,
                'skills' => $this->craftingSkillData($character, $type),
                'currency' => [
                    'type' => $type->requiredCurrency(),
                    'amount' => $this->currencyAmount($character, $type->requiredCurrency()),
                ],
                'counts' => [
                    'crafted' => $batchCrafting->crafted_count,
                    'sold' => $batchCrafting->sold_count,
                    'destroyed' => $batchCrafting->destroyed_count,
                    'listed' => $batchCrafting->listed_count,
                    'disenchanted' => $this->countActionStatus($actionLog, 'disenchanted'),
                    'kept' => $batchCrafting->kept_count,
                    'applied' => $batchCrafting->applied_count,
                    'skipped' => $batchCrafting->skipped_count,
                    'failed' => $batchCrafting->failed_count,
                ],
                'kept_set_summary' => $this->keptSetSummary($batchCrafting),
                'batch_crafting_set' => [
                    'current_slots' => $batchCraftingSet?->currentSlotCount() ?? 0,
                    'max_slots' => $batchCraftingSet?->max_slots ?? InventorySet::BATCH_CRAFTING_MAX_SLOTS,
                    'remaining_slots' => $batchCraftingSet?->remainingSlots() ?? InventorySet::BATCH_CRAFTING_MAX_SLOTS,
                ],
                'action_log' => $actionLog,
            ],
        ];
    }

    public function process(BatchCrafting $batchCrafting): BatchCrafting
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

        if ($this->isInventoryFull($character)) {
            return $this->complete($batchCrafting, BatchCraftingEndReason::NO_INVENTORY_SPACE);
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

        if ($this->currencyAmount($character, $currency) <= 0) {
            return $this->complete($batchCrafting, $this->currencyEndReason($currency));
        }

        try {
            $result = $this->processor->processOneTick($batchCrafting, $character);
        } catch (\Throwable $e) {
            $this->logger()->exceptionCaught($batchCrafting, $e);

            return $this->complete($batchCrafting, BatchCraftingEndReason::FAILED);
        }

        if (isset($result['end_reason'])) {
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

            $batchCrafting->increment($column, $increment);
        }

        if (! empty($counts) || ! empty($result['actions'] ?? [])) {
            $this->appendActionLog($batchCrafting, $counts, $result['actions'] ?? []);
        }

        if (! empty($result['actions'] ?? [])) {
            $this->logActions($batchCrafting, $result['actions']);
        }

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
        $batchCrafting->update(array_merge([
            'completed_at' => now(),
            'ended_reason' => $reason->value,
            'status' => 'completed',
            'panel_dismissed_at' => null,
        ], $extra));

        if ($reason === BatchCraftingEndReason::CANCELLED) {
            $this->logger()->batchCancelled($batchCrafting);
        } elseif ($reason === BatchCraftingEndReason::COMPLETED_DURATION || $reason === BatchCraftingEndReason::AMOUNT_REACHED || $reason === BatchCraftingEndReason::ALL_OILS_APPLIED) {
            $this->logger()->batchCompleted($batchCrafting, $reason);
        } else {
            $this->logger()->hardStop($batchCrafting, $reason);
        }

        $user = $batchCrafting->user()->first();

        if (! is_null($user)) {
            $reasonLabel = ucwords(str_replace('_', ' ', $reason->value));
            event(new ServerMessageEvent($user, 'Batch crafting has ended. Reason: ' . $reasonLabel));
        }

        event(new BatchCraftingStatusUpdated($batchCrafting->user_id));
        event(new BatchCraftingMonitoringUpdated($batchCrafting->character_id));

        return $batchCrafting->refresh();
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

        if (count($log) > 50) {
            $log = array_slice($log, -50);
        }

        $batchCrafting->update(['action_log' => $log]);
    }

    private function craftingSkillData(Character $character, BatchCraftingType $type): array
    {
        $skillNameMap = $this->relevantSkillNameMap($type);

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
                'current_xp' => $skill->xp,
                'next_level_xp' => $skill->xp_max,
                'xp_percent' => $xpPercent,
                'is_maxed' => $isMaxed,
            ];
        }

        return $result;
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

    private function isInventoryFull(Character $character): bool
    {
        return $character->getInventoryCount() >= $character->inventory_max;
    }

    private function currencyAmount(Character $character, string $currency): int
    {
        return (int) ($character->{$currency} ?? 0);
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
        $requested = $progress['craft_amount'] ?? $progress['alchemy_amount'] ?? $progress['holy_oil_requested_applications'] ?? null;
        $completed = $progress['craft_specific_count'] ?? $progress['craft_enchant_specific_count'] ?? $progress['alchemy_amount_count'] ?? $progress['holy_oil_completed_applications'] ?? null;

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
        if ($type === BatchCraftingType::HOLY_OILS) {
            return self::IMMEDIATE_DELAY_SECONDS;
        }

        if ($type === BatchCraftingType::ALCHEMY && ($progress['alchemy_mode'] ?? 'experience') === 'amount') {
            return self::IMMEDIATE_DELAY_SECONDS;
        }

        if (in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true) && ($progress['craft_mode'] ?? 'experience') === 'specific_item') {
            return self::IMMEDIATE_DELAY_SECONDS;
        }

        return self::RECURRING_DELAY_SECONDS;
    }

    private function validatedProgress(Character $character, BatchCraftingType $type, array $data): array
    {
        $progress = $data['progress'] ?? [];

        if (in_array($type, [BatchCraftingType::CRAFT, BatchCraftingType::CRAFT_AND_ENCHANT], true)) {
            $mode = $progress['craft_mode'] ?? 'experience';

            if ($mode === 'experience') {
                $craftingSkills = $character->skills()
                    ->whereHas('baseSkill', fn ($query) => $query->whereIn('name', [
                        'Weapon Crafting',
                        'Armour Crafting',
                        'Ring Crafting',
                        'Spell Crafting',
                    ]))
                    ->with('baseSkill')
                    ->get();

                if ($craftingSkills->isNotEmpty() && $craftingSkills->every(fn ($skill) => $skill->level >= $skill->max_level)) {
                    throw ValidationException::withMessages([
                        'progress.craft_mode' => 'Crafting skills are already maxed.',
                    ]);
                }
            }

            if ($mode === 'specific_item') {
                $this->validateSpecificCraftItem($character, $progress);
            }

            if ($type === BatchCraftingType::CRAFT_AND_ENCHANT && $mode === 'specific_item') {
                $this->validateEnchantAffixes($progress['enchant_affix_ids'] ?? []);
            }
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

        return $progress;
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

    private function validateEnchantAffixes(array $affixIds): void
    {
        if (empty($affixIds)) {
            throw ValidationException::withMessages([
                'progress.enchant_affix_ids' => 'Select at least one enchantment.',
            ]);
        }

        if (ItemAffix::whereIn('id', $affixIds)->count() !== count($affixIds)) {
            throw ValidationException::withMessages([
                'progress.enchant_affix_ids' => 'One or more selected enchantments are invalid.',
            ]);
        }
    }

    private function rejectMaxedSkill(Character $character, string $skillName, string $field): void
    {
        $skill = $character->skills()
            ->whereHas('baseSkill', fn ($query) => $query->where('name', $skillName))
            ->with('baseSkill')
            ->first();

        if (! is_null($skill) && $skill->level >= $skill->max_level) {
            throw ValidationException::withMessages([
                $field => $skillName . ' is already maxed.',
            ]);
        }
    }

    private function craftingSkillName(string $craftingType): string
    {
        if (in_array($craftingType, ['dagger', 'sword', 'claw', 'wand', 'censer', 'stave', 'hammer', 'bow', 'gun', 'fan', 'mace', 'scratch-awl', 'weapon'], true)) {
            return 'Weapon Crafting';
        }

        return match ($craftingType) {
            'ring' => 'Ring Crafting',
            'spell', 'spell_damage', 'spell_healing' => 'Spell Crafting',
            default => 'Armour Crafting',
        };
    }

    private function validateHolyOilSelections(Character $character, array $data): void
    {
        $selectedItems = $data['selected_items'] ?? [];
        $selectedOils = $data['selected_oils'] ?? [];

        if (empty($selectedItems)) {
            throw ValidationException::withMessages([
                'selected_items' => 'Select at least one eligible item for Holy Oils.',
            ]);
        }

        if (empty($selectedOils)) {
            throw ValidationException::withMessages([
                'selected_oils' => 'Select at least one usable Holy Oil.',
            ]);
        }

        foreach ($selectedItems as $itemId) {
            $slot = $character->inventory?->slots()
                ->where('item_id', $itemId)
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
