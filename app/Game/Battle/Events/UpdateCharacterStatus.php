<?php

namespace App\Game\Battle\Events;

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
     * @param Character $character
     * @param ?AttackTimerService $attackTimerService
     * @param ?float $attackCooldownSecondsOverride
     */
    public function __construct(Character $character, ?AttackTimerService $attackTimerService = null, ?float $attackCooldownSecondsOverride = null)
    {
        $automationRestrictionService = new AutomationRestrictionService();
        $attackTimerService ??= new AttackTimerService($automationRestrictionService);
        $character = $attackTimerService->normalizeExpiredAttackTimer($character);

        $this->characterStatuses = [
            'can_attack' => $character->can_attack,
            'can_attack_again_at' => $this->remainingAttackCooldownSeconds($character->can_attack_again_at, $attackCooldownSecondsOverride),
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
            'active_automation' => $this->activeAutomation($character, $automationRestrictionService),
            'automation_completed_at' => $this->getTimeLeftOnAutomation($character, $automationRestrictionService),
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

    /**
     * Get the remaining manual attack cooldown, in seconds, to a tenth of a second.
     *
     * @param ?Carbon $canAttackAgainAt
     * @param ?float $secondsOverride
     * @return float
     */
    private function remainingAttackCooldownSeconds(?Carbon $canAttackAgainAt, ?float $secondsOverride): float
    {
        if (is_null($canAttackAgainAt)) {
            return 0.0;
        }

        if (! is_null($secondsOverride)) {
            return max(0.0, round($secondsOverride, 1));
        }

        $remainingMilliseconds = $canAttackAgainAt->getPreciseTimestamp(3) - now()->getPreciseTimestamp(3);

        return max(0.0, round($remainingMilliseconds / 1000, 1));
    }

    /**
     * Return the Character's active automation's remaining timer, in seconds.
     *
     * @param Character $character
     * @param AutomationRestrictionService $automationRestrictionService
     * @return int
     */
    private function getTimeLeftOnAutomation(Character $character, AutomationRestrictionService $automationRestrictionService): int
    {
        $automation = $this->activeAutomation($character, $automationRestrictionService);

        if (! is_null($automation)) {
            return $automation['timer_seconds'];
        }

        return 0;
    }

    /**
     * Resolve the Character's currently active automation identity, if any.
     *
     * @param Character $character
     * @param AutomationRestrictionService $automationRestrictionService
     * @return ?array
     */
    private function activeAutomation(Character $character, AutomationRestrictionService $automationRestrictionService): ?array
    {
        $automation = $automationRestrictionService->activeAutomation($character);

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

    /**
     * Determine whether the Delve panel should be visible for the Character.
     *
     * @param Character $character
     * @return bool
     */
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

    /**
     * Determine whether the Character's Alchemy skill is locked.
     *
     * @param Character $character
     * @return bool
     */
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

    /**
     * Determine whether the Character is currently standing at the Delve location.
     *
     * @param Character $character
     * @return bool
     */
    private function isAtDelveLocation(Character $character): bool
    {
        $characterMap = $character->map;

        $questItemForDelve = Item::where('effect', ItemEffectType::DELVE->value)->first();

        if (is_null($questItemForDelve)) {
            return false;
        }

        $location = Location::where('game_map_id', $characterMap->game_map_id)->where('x', $characterMap->character_position_x)->where('y', $characterMap->character_position_y)
            ->where('type', LocationType::CAVE_OF_SHADOWS->value)->first();

        $characterHasItem = $character->inventory->slots->filter(function ($slot) use ($questItemForDelve) {
            return $slot->item_id === $questItemForDelve->id;
        })->isNotEmpty();

        return ! is_null($location) && $characterHasItem;
    }

    /**
     * Determine whether the Character holds the Delve pact-choice quest item.
     *
     * @param Character $character
     * @return bool
     */
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
    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('update-character-status-'.$this->user->id);
    }
}
