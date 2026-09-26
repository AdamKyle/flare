<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\GameClass;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Services\AttackTimerService;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterAttack\Builders\ClassAttackBuilder;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Maps\Values\LocationBasedCraftingOptions;
use App\Game\Maps\Values\LocationType;
use Carbon\Carbon;

class CharacterSheetBaseInfoTransformer extends BaseTransformer
{
    private bool $ignoreReductions = false;

    /**
     * @param CharacterStatBuilder $characterStatBuilder
     * @param AttackTimerService $attackTimerService
     * @param CharacterInventoryCountTransformer $characterInventoryCountTransformer
     * @param AutomationRestrictionService $automationRestrictionService
     */
    public function __construct(
        private readonly CharacterStatBuilder $characterStatBuilder,
        private readonly AttackTimerService $attackTimerService,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
        private readonly AutomationRestrictionService $automationRestrictionService,
    ) {}

    /**
     * Set whether stat reductions should be ignored when building the character sheet.
     *
     * @param bool $ignoreReductions
     * @return void
     */
    public function setIgnoreReductions(bool $ignoreReductions): void
    {
        $this->ignoreReductions = $ignoreReductions;
    }

    /**
     * Build the character sheet's base info payload for the given Character.
     *
     * @param Character $character
     * @return array
     */
    public function transform(Character $character): array
    {
        $character = $this->attackTimerService->normalizeExpiredAttackTimer($character);
        $characterStatBuilder = $this->characterStatBuilder->setCharacter($character, $this->ignoreReductions);
        $gameClass = GameClass::find($character->game_class_id);
        $factionLoyalty = $character->factionLoyalties()->where('is_pledged', '=', true)->first();
        $factionLoyaltyWarningNotices = $this->getFactionLoyaltyWarningNotices($character);
        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        return [
            'id' => $character->id,
            'user_id' => $character->user_id,
            'gem_world_introduction_acknowledged_at' => $character->gem_world_introduction_acknowledged_at,
            'name' => $character->name,
            'class' => $gameClass->name,
            'class_id' => $gameClass->id,
            'race' => $character->race->name,
            'race_id' => $character->race->id,
            'to_hit_stat' => $character->class->to_hit_stat,
            'damage_stat' => $character->class->damage_stat,
            'level' => $character->level,
            'max_level' => $this->getMaxLevel($character),
            'xp' => $character->xp,
            'xp_next' => $character->xp_next,
            'str_modded' => $characterStatBuilder->statMod('str'),
            'dur_modded' => $characterStatBuilder->statMod('dur'),
            'dex_modded' => $characterStatBuilder->statMod('dex'),
            'chr_modded' => $characterStatBuilder->statMod('chr'),
            'int_modded' => $characterStatBuilder->statMod('int'),
            'agi_modded' => $characterStatBuilder->statMod('agi'),
            'focus_modded' => $characterStatBuilder->statMod('focus'),
            'attack' => $characterStatBuilder->buildTotalAttack(),
            'health' => $characterStatBuilder->buildHealth(),
            'healing_amount' => $characterStatBuilder->buildHealing(),
            'voided_healing_amount' => $characterStatBuilder->buildHealing(true),
            'ac' => $characterStatBuilder->buildDefence(),
            'extra_action_chance' => (new ClassAttackBuilder($character))->buildAttackData(),
            'fight_time_out_mod_bonus' => $characterStatBuilder->buildTimeOutModifier('fight_time_out'),
            'movement_time_out_mod_bonus' => $characterStatBuilder->buildTimeOutModifier('move_time_out'),
            'gold' => $character->gold,
            'gold_dust' => $character->gold_dust,
            'shards' => $character->shards,
            'copper_coins' => $character->copper_coins,
            'is_dead' => $character->is_dead,
            'can_craft' => $character->can_craft,
            'can_attack' => $character->can_attack,
            'can_spin' => $character->can_spin,
            'can_move' => $character->can_move,
            'can_engage_celestials' => $character->can_engage_celestials,
            'can_engage_celestials_again_at' => $this->remainingSecondsUntil($character->can_engage_celestials_again_at),
            'can_attack_again_at' => $this->remainingSecondsUntil($character->can_attack_again_at),
            'can_craft_again_at' => $this->remainingSecondsUntil($character->can_craft_again_at),
            'can_spin_again_at' => $this->remainingSecondsUntil($character->can_spin_again_at),
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
            'can_set_delve_pack' => $this->canSetPactOptionsForDelve($character),
            'is_at_delve_location' => $this->isAtDelveLocation($character),
            'active_automation' => $this->activeAutomation($character),
            'automation_completed_at' => $this->getTimeLeftOnAutomation($character),
            'is_silenced' => $character->user->is_silenced,
            'can_talk_again_at' => $character->user->can_talk_again_at,
            'show_intro_page' => $character->user->show_intro_page,
            'can_move_again_at' => $this->remainingSecondsUntil($character->can_move_again_at),
            'force_name_change' => $character->force_name_change,
            'is_alchemy_locked' => $this->isAlchemyLocked($character),
            'can_use_work_bench' => $locationBasedCraftingOptions->canUseWorkBench,
            'can_access_queen' => $locationBasedCraftingOptions->canUseQueenOfHearts,
            'can_access_hell_forged' => $character->map?->gameMap?->mapType()->isHell() ?? false,
            'can_access_purgatory_chains' => $character->map?->gameMap?->mapType()->isPurgatory() ?? false,
            'can_access_labyrinth_oracle' => $locationBasedCraftingOptions->canAccessLabyrinthOracle,
            'can_access_seer_camp' => $locationBasedCraftingOptions->canAccessSeerCamp,
            'can_access_twisted_earth' => $character->map?->gameMap?->mapType()->isTwistedMemories() ?? false,
            'is_in_timeout' => ! is_null($character->user->timeout_until),
            'can_see_pledge_tab' => ! is_null($factionLoyalty),
            'pledged_to_faction_id' => ! is_null($factionLoyalty) ? $factionLoyalty->faction_id : null,
            'current_fame_tasks' => $this->getFactionTasks($factionLoyalty),
            'has_faction_loyalty_warning' => count($factionLoyaltyWarningNotices) > 0,
            'faction_loyalty_warning_notices' => $factionLoyaltyWarningNotices,
            'resurrection_chance' => $characterStatBuilder->buildResurrectionChance(),
            'is_admin' => $character->user->hasRole('Admin'),
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
        ];
    }

    /**
     * Return the Character's undismissed Faction Loyalty automation warning notices.
     *
     * @param Character $character
     * @return array
     */
    private function getFactionLoyaltyWarningNotices(Character $character): array
    {
        return FactionLoyaltyAutomationWarning::where('character_id', $character->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (FactionLoyaltyAutomationWarning $warning): array {
                return [
                    'id' => $warning->id,
                    'type' => $warning->type,
                    'message' => $warning->message,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Return the non-bounty fame tasks for the Faction Loyalty's currently helped NPC.
     *
     * @param ?FactionLoyalty $factionLoyalty
     * @return array
     */
    private function getFactionTasks(?FactionLoyalty $factionLoyalty = null): array
    {

        if (is_null($factionLoyalty)) {
            return [];
        }

        $factionLoyaltyNpc = $factionLoyalty->factionLoyaltyNpcs->where('currently_helping', true)->first();

        if (is_null($factionLoyaltyNpc)) {
            return [];
        }

        return array_values(collect($factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks)->filter(function ($task) {
            return $task['type'] !== 'bounty';
        })->toArray());
    }

    /**
     * Return the Character's active automation's remaining timer, in seconds.
     *
     * @param Character $character
     * @return int
     */
    private function getTimeLeftOnAutomation(Character $character): int
    {
        $automation = $this->activeAutomation($character);

        if (! is_null($automation)) {
            return $automation['timer_seconds'];
        }

        return 0;
    }

    /**
     * Resolve the Character's currently active automation identity, if any.
     *
     * @param Character $character
     * @return ?array
     */
    private function activeAutomation(Character $character): ?array
    {
        $automation = $this->automationRestrictionService->activeAutomation($character);

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
            'timer_seconds' => $this->remainingSecondsUntil($automation->completed_at),
        ];
    }

    /**
     * Return the remaining whole seconds until the given timestamp, or zero when it is past or missing.
     *
     * @param ?Carbon $timestamp
     * @return int
     */
    private function remainingSecondsUntil(?Carbon $timestamp): int
    {
        if (is_null($timestamp) || $timestamp->isPast()) {
            return 0;
        }

        return now()->diffInSeconds($timestamp, false);
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
     * Determine whether the Character stands at a Cave of Shadows while holding the Delve access quest item.
     *
     * @param Character $character
     * @return bool
     */
    private function isAtDelveLocation(Character $character): bool
    {
        $questItemForDelve = Item::where('effect', ItemEffectType::DELVE->value)->first();

        if (is_null($questItemForDelve)) {
            return false;
        }

        $characterHasItem = $character->inventory->slots->contains('item_id', $questItemForDelve->id);

        if (! $characterHasItem) {
            return false;
        }

        return Location::where('game_map_id', $character->map->game_map_id)
            ->where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('type', LocationType::CAVE_OF_SHADOWS->value)
            ->exists();
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
}
