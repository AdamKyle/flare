<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Game\Automation\Values\AutomationType;

class AutomationRestrictionService
{
    public const MANUAL_FIGHTING = 'manual_fighting';

    public const CELESTIAL_FIGHTING = 'celestial_fighting';

    public const CELESTIAL_CONJURING = 'celestial_conjuring';

    public const PCT = 'pct';

    public const DIRECTIONAL_MOVEMENT = 'directional_movement';

    public const TELEPORT = 'teleport';

    public const SET_SAIL = 'set_sail';

    public const TRAVERSE = 'traverse';

    public const ENTER_LOCATION = 'enter_location';

    public const START_DELVE = 'start_delve';

    public const START_EXPLORATION = 'start_exploration';

    public const START_FACTION_LOYALTY = 'start_faction_loyalty';

    public const START_CRAFTING = 'start_crafting';

    public const START_ITEM_CRAFTING = 'start_item_crafting';

    public const KINGDOM_MANAGEMENT = 'kingdom_management';

    public const PLAYER_SKILLS = 'player_skills';

    public const CLASS_RANKS = 'class_ranks';

    public const REGULAR_QUESTS = 'regular_quests';

    public const INVENTORY_MANAGEMENT = 'inventory_management';

    public const EQUIPMENT_MANAGEMENT = 'equipment_management';

    /**
     * Resolve the Character's current active automation, regardless of type.
     *
     * @param Character $character
     * @return ?CharacterAutomation
     */
    public function activeAutomation(Character $character): ?CharacterAutomation
    {
        return $character->currentAutomations()
            ->where('completed_at', '>', now())
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Resolve the Character's current active automation of the given type.
     *
     * @param Character $character
     * @param string $type
     * @return ?CharacterAutomation
     */
    public function activeAutomationOfType(Character $character, string $type): ?CharacterAutomation
    {
        return $character->currentAutomations()
            ->where('type', $type)
            ->where('completed_at', '>', now())
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Determine whether the requested action is currently blocked for the Character.
     *
     * @param Character $character
     * @param string $action
     * @param ?Location $destinationLocation
     * @return bool
     */
    public function isBlocked(Character $character, string $action, ?Location $destinationLocation = null): bool
    {
        return ! is_null($this->blockedContext($character, $action, $destinationLocation));
    }

    /**
     * Resolve the blocking automation context for the requested action, if any.
     *
     * @param Character $character
     * @param string $action
     * @param ?Location $destinationLocation
     * @return ?array
     */
    public function blockedContext(Character $character, string $action, ?Location $destinationLocation = null): ?array
    {
        $batchCrafting = $this->activeBatchCrafting($character);

        if (! is_null($batchCrafting) && $this->batchCraftingBlocksAction($action)) {
            return [
                'automation' => $batchCrafting,
                'automation_name' => 'Batch Crafting',
                'message' => 'You cannot do that while Batch Crafting is running. Cancel it first.',
            ];
        }

        $automation = $this->activeAutomation($character);

        if (is_null($automation)) {
            return null;
        }

        if (! $this->automationBlocksAction($automation, $action, $destinationLocation)) {
            return null;
        }

        return [
            'automation' => $automation,
            'automation_name' => $this->automationName($automation),
            'message' => $this->blockedMessage($automation, $action),
        ];
    }

    /**
     * Build the player-facing message explaining why the action is blocked.
     *
     * @param CharacterAutomation $automation
     * @param ?string $action
     * @return string
     */
    public function blockedMessage(CharacterAutomation $automation, ?string $action = null): string
    {
        $automationType = AutomationType::from($automation->type);

        if ($action === self::START_FACTION_LOYALTY && ($automationType->isExploring() || $automationType->isDelve())) {
            $automationName = $this->automationName($automation);

            return 'You are currently doing '.$automationName.'. This action cannot be completed right now. Please cancel '.$automationName.' first.';
        }

        return 'You cannot do that while '.$this->automationName($automation).' automation is running. Cancel it first.';
    }

    /**
     * Determine whether the given Location is a special Exploration location.
     *
     * @param ?Location $location
     * @return bool
     */
    public function isSpecialExplorationLocation(?Location $location): bool
    {
        if (is_null($location)) {
            return false;
        }

        return ! is_null($location->type);
    }

    /**
     * Determine whether the active automation blocks the requested action.
     *
     * @param CharacterAutomation $automation
     * @param string $action
     * @param ?Location $destinationLocation
     * @return bool
     */
    private function automationBlocksAction(CharacterAutomation $automation, string $action, ?Location $destinationLocation = null): bool
    {
        $automationType = AutomationType::from($automation->type);

        if ($automationType->isFactionLoyalty()) {
            return in_array($action, [
                self::START_DELVE,
                self::START_EXPLORATION,
                self::MANUAL_FIGHTING,
                self::START_ITEM_CRAFTING,
                self::PCT,
                self::CELESTIAL_FIGHTING,
                self::CELESTIAL_CONJURING,
                self::START_FACTION_LOYALTY,
                self::KINGDOM_MANAGEMENT,
                self::PLAYER_SKILLS,
                self::CLASS_RANKS,
                self::REGULAR_QUESTS,
                self::EQUIPMENT_MANAGEMENT,
            ]);
        }

        if ($automationType->isDelve()) {
            return in_array($action, [
                self::START_EXPLORATION,
                self::MANUAL_FIGHTING,
                self::START_FACTION_LOYALTY,
                self::PCT,
                self::CELESTIAL_FIGHTING,
                self::CELESTIAL_CONJURING,
                self::DIRECTIONAL_MOVEMENT,
                self::ENTER_LOCATION,
                self::TELEPORT,
                self::SET_SAIL,
                self::TRAVERSE,
                self::START_DELVE,
                self::KINGDOM_MANAGEMENT,
                self::PLAYER_SKILLS,
                self::CLASS_RANKS,
                self::REGULAR_QUESTS,
                self::EQUIPMENT_MANAGEMENT,
            ]);
        }

        return $this->explorationBlocksAction($automation, $action, $destinationLocation);
    }

    /**
     * Resolve the Character's active Batch Crafting run, if any.
     *
     * @param Character $character
     * @return ?BatchCrafting
     */
    private function activeBatchCrafting(Character $character): ?BatchCrafting
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

    /**
     * Determine whether an active Batch Crafting run blocks the requested action.
     *
     * @param string $action
     * @return bool
     */
    private function batchCraftingBlocksAction(string $action): bool
    {
        return in_array($action, [
            self::START_FACTION_LOYALTY,
            self::START_ITEM_CRAFTING,
            self::KINGDOM_MANAGEMENT,
            self::PLAYER_SKILLS,
            self::CLASS_RANKS,
            self::REGULAR_QUESTS,
            self::INVENTORY_MANAGEMENT,
        ]);
    }

    /**
     * Determine whether an active Exploration automation blocks the requested action.
     *
     * @param CharacterAutomation $automation
     * @param string $action
     * @param ?Location $destinationLocation
     * @return bool
     */
    private function explorationBlocksAction(CharacterAutomation $automation, string $action, ?Location $destinationLocation = null): bool
    {
        if (in_array($action, [
            self::START_DELVE,
            self::START_FACTION_LOYALTY,
            self::MANUAL_FIGHTING,
            self::PCT,
            self::CELESTIAL_FIGHTING,
            self::CELESTIAL_CONJURING,
            self::TELEPORT,
            self::SET_SAIL,
            self::TRAVERSE,
            self::START_EXPLORATION,
            self::KINGDOM_MANAGEMENT,
            self::PLAYER_SKILLS,
            self::CLASS_RANKS,
            self::REGULAR_QUESTS,
            self::EQUIPMENT_MANAGEMENT,
        ])) {
            return true;
        }

        if ($automation->started_in_special_location) {
            return in_array($action, [
                self::DIRECTIONAL_MOVEMENT,
                self::ENTER_LOCATION,
            ]);
        }

        if (in_array($action, [
            self::DIRECTIONAL_MOVEMENT,
            self::ENTER_LOCATION,
        ])) {
            return $this->isSpecialExplorationLocation($destinationLocation);
        }

        return false;
    }

    /**
     * Resolve the player-facing name for the given automation's type.
     *
     * @param CharacterAutomation $automation
     * @return string
     */
    private function automationName(CharacterAutomation $automation): string
    {
        $automationType = AutomationType::from($automation->type);

        if ($automationType->isExploring()) {
            return 'Exploration';
        }

        if ($automationType->isDelve()) {
            return 'Delve';
        }

        return 'Faction Loyalty';
    }
}
