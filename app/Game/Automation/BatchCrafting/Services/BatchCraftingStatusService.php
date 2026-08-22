<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Status\BatchCraftingStatusSectionResolver;

class BatchCraftingStatusService
{
    public function __construct(
        private readonly BatchCraftingCapabilityService $batchCraftingCapabilityService,
        private readonly BatchCraftingStatusSectionResolver $statusSectionResolver,
    ) {}

    /**
     * Build the complete Batch Crafting status snapshot, including full chart history and capabilities.
     *
     * @param  Character  $character  The character requesting status.
     * @return array The complete Batch Crafting status snapshot.
     */
    public function build(Character $character): array
    {
        return $this->buildSnapshot($character, includeCapabilities: true);
    }

    /**
     * Build the Batch Crafting status snapshot for a websocket broadcast.
     *
     * Carries an empty chart-point history and does not recalculate the general setup
     * capabilities, since a runtime broadcast always belongs to an already-visible batch.
     *
     * @param  Character  $character  The character the broadcast belongs to.
     * @return array The Batch Crafting runtime status snapshot.
     */
    public function buildForBroadcast(Character $character): array
    {
        $snapshot = $this->buildSnapshot($character, includeCapabilities: false);

        if (is_null($snapshot['batch'])) {
            return $snapshot;
        }

        $snapshot['batch']['chart_points'] = [];

        return $snapshot;
    }

    /**
     * Build the shared Batch Crafting status snapshot for the character.
     *
     * @param  Character  $character  The character requesting status.
     * @param  bool  $includeCapabilities  Whether to recalculate the general setup capabilities.
     * @return array The Batch Crafting status snapshot.
     */
    private function buildSnapshot(Character $character, bool $includeCapabilities): array
    {
        $batchCrafting = $this->resolveVisibleBatchCrafting($character);
        $showInfo = ! $this->hasAcknowledgedInfo($character);
        $capabilities = $this->resolveCapabilities($character, $batchCrafting, $includeCapabilities);

        if (is_null($batchCrafting)) {
            return [
                'active' => false,
                'is_running' => false,
                'is_scheduled' => false,
                'is_processing' => false,
                'is_waiting' => false,
                'is_visible' => false,
                'can_cancel' => false,
                'can_dismiss' => false,
                'show_info' => $showInfo,
                'capabilities' => $capabilities,
                'batch' => null,
            ];
        }

        $isRunning = $batchCrafting->isRunning();
        $progress = $batchCrafting->progress;
        $processingStartedAt = $progress['processing_started_at'];
        $nextAttemptAt = $progress['next_attempt_at'] ?? null;

        return [
            'active' => $isRunning,
            'is_running' => $isRunning,
            'is_scheduled' => $isRunning && is_null($processingStartedAt),
            'is_processing' => $isRunning && ! is_null($processingStartedAt) && is_null($nextAttemptAt),
            'is_waiting' => $isRunning && ! is_null($processingStartedAt) && ! is_null($nextAttemptAt),
            'is_visible' => true,
            'can_cancel' => $isRunning,
            'can_dismiss' => ! $isRunning,
            'show_info' => $showInfo,
            'capabilities' => $capabilities,
            'batch' => $this->buildBatch($character, $batchCrafting),
        ];
    }

    /**
     * Resolve the general setup capabilities, only when required by the caller or by an empty batch state.
     *
     * A runtime broadcast for an already-visible batch does not need setup capabilities, so it
     * omits them entirely (`null`) instead of recalculating Experience/Event capability work or
     * sending deliberately false placeholder facts. The frontend status provider retains the
     * last authoritative capabilities it received until a later status genuinely recalculates
     * them (initial/reopen GET, or the no-visible-batch state reached after completion/dismissal).
     *
     * @param  Character  $character  The character requesting status.
     * @param  BatchCrafting|null  $batchCrafting  The character's visible Batch Crafting record, if any.
     * @param  bool  $includeCapabilities  Whether the caller explicitly requested capability recalculation.
     * @return array|null The authoritative general setup capability facts, or null for a runtime broadcast.
     */
    private function resolveCapabilities(Character $character, ?BatchCrafting $batchCrafting, bool $includeCapabilities): ?array
    {
        if (! $includeCapabilities && ! is_null($batchCrafting)) {
            return null;
        }

        return $this->batchCraftingCapabilityService->build($character);
    }

    /**
     * Build the nested player-facing batch payload for a visible Batch Crafting record.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @return array The nested batch status payload.
     */
    private function buildBatch(Character $character, BatchCrafting $batchCrafting): array
    {
        $progress = $batchCrafting->progress;
        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $mode = $type->modeFromProgress($progress);
        $section = $this->statusSectionResolver->resolve($type, $mode);
        $modeFacts = $section->build($character, $batchCrafting, $progress);

        return [
            'id' => $batchCrafting->id,
            'batch_type' => $batchCrafting->batch_type,
            'mode' => $mode,
            'craft_mode' => $progress['craft_mode'] ?? null,
            'disposition' => $batchCrafting->disposition,
            'status' => $batchCrafting->status,
            'ended_reason' => $batchCrafting->ended_reason,
            'current_item_id' => $modeFacts['current_item_id'],
            'current_item_name' => $modeFacts['current_item_name'],
            'current_crafting_type' => $modeFacts['current_crafting_type'],
            'current_prefix_name' => $modeFacts['current_prefix_name'] ?? null,
            'current_suffix_name' => $modeFacts['current_suffix_name'] ?? null,
            'output_destination' => $progress['output_destination'] ?? null,
            'listing_price' => $progress['listing_price'] ?? null,
            'destination_set_id' => $modeFacts['destination_set_id'],
            'destination_set_name' => $modeFacts['destination_set_name'],
            'requested_amount' => $modeFacts['requested_amount'],
            'completed_amount' => $modeFacts['completed_amount'],
            'remaining_amount' => $modeFacts['remaining_amount'],
            'crafted_count' => $batchCrafting->crafted_count,
            'kept_count' => $batchCrafting->kept_count,
            'sold_count' => $batchCrafting->sold_count,
            'destroyed_count' => $batchCrafting->destroyed_count,
            'listed_count' => $batchCrafting->listed_count,
            'applied_count' => $batchCrafting->applied_count,
            'disenchanted_count' => $progress['disenchanted_count'] ?? 0,
            'used_count' => $progress['used_count'] ?? 0,
            'failed_count' => $batchCrafting->failed_count,
            'skipped_count' => $batchCrafting->skipped_count,
            'gold_spent' => $progress['gold_spent_total'],
            'gold_gained' => $progress['gold_gained_total'],
            'gold_left' => $character->gold,
            'gold_dust_spent' => $progress['gold_dust_spent_total'] ?? 0,
            'gold_dust_left' => $character->gold_dust,
            'shards_spent' => $progress['shards_spent_total'] ?? 0,
            'shards_left' => $character->shards,
            'copper_coins_spent' => $progress['copper_coins_spent_total'] ?? 0,
            'copper_coins_left' => $character->copper_coins,
            'started_at' => $batchCrafting->started_at->toJSON(),
            'scheduled_for' => $progress['scheduled_for'],
            'processing_started_at' => $progress['processing_started_at'],
            'next_attempt_at' => $progress['next_attempt_at'] ?? null,
            'completed_at' => $batchCrafting->completed_at?->toJSON(),
            'destination_capacity' => $modeFacts['destination_capacity'],
            'chart_points' => $progress['chart_points'],
            'set_progress' => $modeFacts['set_progress'],
            'experience_progress' => $modeFacts['experience_progress'],
            'event_progress' => $modeFacts['event_progress'],
            'alchemy_amount_progress' => $modeFacts['alchemy_amount_progress'] ?? null,
            'alchemy_experience_progress' => $modeFacts['alchemy_experience_progress'] ?? null,
            'holy_oils_progress' => $modeFacts['holy_oils_progress'] ?? null,
            'trinketry_progress' => $modeFacts['trinketry_progress'] ?? null,
        ];
    }

    /**
     * Return the character's currently visible Batch Crafting record, if any.
     *
     * @param  Character  $character  The character being checked.
     * @return BatchCrafting|null The visible record, or null when nothing is visible.
     */
    private function resolveVisibleBatchCrafting(Character $character): ?BatchCrafting
    {
        return BatchCrafting::where('character_id', $character->id)
            ->where('status', '!=', BatchCraftingStatus::INFO->value)
            ->where(function ($query) {
                $query->where(function ($runningQuery) {
                    $runningQuery->whereNull('completed_at')->whereNull('cancelled_at');
                })->orWhere(function ($endedQuery) {
                    $endedQuery->whereNull('panel_dismissed_at')
                        ->where(function ($reasonQuery) {
                            $reasonQuery->whereNotNull('completed_at')->orWhereNotNull('cancelled_at');
                        });
                });
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Determine whether the character has acknowledged the Batch Crafting introduction.
     *
     * @param  Character  $character  The character being checked.
     * @return bool True when the character has acknowledged the introduction.
     */
    private function hasAcknowledgedInfo(Character $character): bool
    {
        return BatchCrafting::where('character_id', $character->id)
            ->where('info_acknowledged', true)
            ->exists();
    }
}
