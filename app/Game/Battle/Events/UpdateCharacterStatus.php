<?php

namespace App\Game\Battle\Events;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\Event;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Skill;
use App\Flare\Models\User;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Automation\Values\AutomationType;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Battle\Services\AttackTimerService;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Events\Concerns\ShouldShowCraftingEventButton;
use App\Game\Events\Concerns\ShouldShowEnchantingEventButton;
use App\Game\Maps\Values\LocationType;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateCharacterStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, ShouldShowCraftingEventButton, ShouldShowEnchantingEventButton;

    public array $characterStatuses = [];

    private User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Character $character, ?AttackTimerService $attackTimerService = null)
    {
        $attackTimerService ??= new AttackTimerService(new AutomationRestrictionService());
        $character = $attackTimerService->normalizeExpiredAttackTimer($character);
        $activeBatchCrafting = $this->activeBatchCrafting($character);

        $this->characterStatuses = [
            'can_attack' => $character->can_attack,
            'can_attack_again_at' => now()->diffInSeconds($character->can_attack_again_at),
            'can_craft' => $character->can_craft,
            'can_craft_again_at' => $character->can_craft_again_at,
            'can_spin' => $character->can_spin,
            'can_spin_again_at' => now()->diffInSeconds($character->can_spin_again_at),
            'can_engage_celestials' => $character->can_engage_celestials,
            'can_engage_celestials_again_at' => now()->diffInSeconds($character->can_engage_celestials_again_at),
            'is_dead' => $character->is_dead,
            'is_automation_running' => $character->currentAutomations()
                ->where('character_id', $character->id)
                ->where('completed_at', '>', now())
                ->exists(),
            'is_faction_loyalty_automation_running' => $character->isFactionLoyaltyAutomationRunning(),
            'is_delve_running' => $character->currentAutomations()
                ->where('character_id', $character->id)
                ->where('type', AutomationType::DELVE->value)
                ->where('completed_at', '>', now())
                ->exists(),
            'is_delve_visible' => $this->isDelveVisible($character),
            'is_batch_crafting_running' => ! is_null($activeBatchCrafting),
            'is_batch_crafting_visible' => $this->visibleBatchCrafting($character),
            'batch_crafting_time_out' => $this->batchCraftingTimeOutSeconds($activeBatchCrafting),
            'is_batch_crafting_experience_mode' => $this->isBatchCraftingExperienceMode($activeBatchCrafting),
            'is_batch_crafting_retry_mode' => $this->isBatchCraftingRetryMode($activeBatchCrafting),
            'active_automation' => $this->activeAutomation($character),
            'automation_completed_at' => $this->getTimeLeftOnAutomation($character),
            'is_silenced' => $character->is_silenced,
            'can_move' => $character->can_move,
            'is_alchemy_locked' => $this->isAlchemyLocked($character),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'show_enchanting_for_event' => $this->shouldShowEnchantingEventButton($character),
            'is_at_delve_location' => $this->isAtDelveLocation($character),
            'can_set_delve_pack' => $this->canSetPactOptionsForDelve($character),
        ];

        $this->user = $character->user;
    }

    private function getTimeLeftOnAutomation(Character $character)
    {
        $automation = $this->activeAutomation($character);

        if (! is_null($automation)) {
            return $automation['timer_seconds'];
        }

        return 0;
    }

    private function activeAutomation(Character $character): ?array
    {
        $automation = $character->currentAutomations()
            ->where('completed_at', '>', now())
            ->orderBy('id')
            ->first();

        if (is_null($automation)) {
            return null;
        }

        $name = match ($automation->type) {
            AutomationType::EXPLORING->value => 'Exploration',
            AutomationType::DELVE->value => 'Delve',
            AutomationType::FACTION_LOYALTY->value => 'Faction Loyalty',
            default => null,
        };

        if (is_null($name)) {
            return null;
        }

        return [
            'type' => $automation->type,
            'name' => $name,
            'timer_seconds' => now()->diffInSeconds($automation->completed_at),
        ];
    }

    private function activeBatchCrafting(Character $character): ?BatchCrafting
    {
        $activeId = BatchCrafting::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->max('id');

        if (is_null($activeId)) {
            return null;
        }

        return BatchCrafting::select(['id', 'character_id', 'batch_type', 'progress', 'started_at', 'ends_at'])
            ->find($activeId);
    }

    private function visibleBatchCrafting(Character $character): bool
    {
        $hasActiveBatchCrafting = BatchCrafting::where('character_id', $character->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->exists();

        if ($hasActiveBatchCrafting) {
            return true;
        }

        return BatchCrafting::where('character_id', $character->id)
            ->whereNull('panel_dismissed_at')
            ->where(function ($query) {
                $query->whereNotNull('completed_at')
                    ->orWhereNotNull('cancelled_at');
            })
            ->exists();
    }

    private function isDelveVisible(Character $character): bool
    {
        $isDelveActive = $character->currentAutomations()
            ->where('character_id', $character->id)
            ->where('type', AutomationType::DELVE->value)
            ->where('completed_at', '>', now())
            ->exists();

        if ($isDelveActive) {
            return true;
        }

        return DelveExploration::where('character_id', $character->id)
            ->whereNotNull('completed_at')
            ->whereNull('panel_dismissed_at')
            ->exists();
    }

    private function batchCraftingTimeOutSeconds(?BatchCrafting $batchCrafting): int
    {
        if (is_null($batchCrafting)) {
            return 0;
        }

        $type = BatchCraftingType::from($batchCrafting->batch_type);
        $progress = $batchCrafting->progress ?? [];

        if ($type->usesEightHourTimer($progress)) {
            return max(0, now()->diffInSeconds($batchCrafting->ends_at, false));
        }

        if (($progress['continuation_state'] ?? null) === 'processing') {
            return 0;
        }

        $nextAttemptAt = $progress['next_attempt_at'] ?? null;

        if (! is_string($nextAttemptAt) || $nextAttemptAt === '') {
            return 0;
        }

        try {
            $pendingUntil = Carbon::parse($nextAttemptAt);
        } catch (\Throwable) {
            return 0;
        }

        return max(0, now()->diffInSeconds($pendingUntil, false));
    }

    private function isBatchCraftingExperienceMode(?BatchCrafting $batchCrafting): bool
    {
        if (is_null($batchCrafting)) {
            return false;
        }

        $type = BatchCraftingType::from($batchCrafting->batch_type);

        return $type->isExperienceMode($batchCrafting->progress ?? []);
    }

    private function isBatchCraftingRetryMode(?BatchCrafting $batchCrafting): bool
    {
        if (is_null($batchCrafting)) {
            return false;
        }

        $progress = $batchCrafting->progress ?? [];
        $tickDelay = (int) ($progress['tick_delay_seconds'] ?? BatchCraftingService::RECURRING_DELAY_SECONDS);

        return $tickDelay < BatchCraftingService::RECURRING_DELAY_SECONDS;
    }

    private function isAlchemyLocked(Character $character): bool
    {

        $alchemySkill = Skill::where('character_id', $character->id)
            ->where('game_skill_id', GameSkill::where(
                'type',
                SkillTypeValue::ALCHEMY->value
            )->first()->id)->first();

        if (is_null($alchemySkill)) {
            return true;
        }

        return $alchemySkill->is_locked;
    }

    private function isAtDelveLocation(Character $character): bool
    {
        $characterMap = $character->map;

        $questItemForDelve = Item::where('effect', ItemEffectType::DELVE->value)->first();

        if (is_null($questItemForDelve)) {
            return false;
        }

        $location = Location::where('game_map_id', $characterMap->game_map_id)->where('x', $characterMap->character_position_x)->where('y', $characterMap->character_position_y)
            ->where('type', LocationType::CAVE_OF_MEMORIES->value)->first();

        $characterHasItem = $character->inventory->slots->filter(function ($slot) use ($questItemForDelve) {
            return $slot->item_id === $questItemForDelve->id;
        })->isNotEmpty();

        return ! is_null($location) && $characterHasItem;
    }

    private function canSetPactOptionsForDelve(Character $character): bool
    {

        $questItemForDelve = Item::where('effect', ItemEffectType::DELVE_PACK_CHOICE->value)->first();

        if (is_null($questItemForDelve)) {
            return false;
        }

        return $character->inventory->slots->filter(function ($slot) use ($questItemForDelve) {
            return $slot->item_id === $questItemForDelve->id;
        })->isNotEmpty();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('update-character-status-'.$this->user->id);
    }
}
