<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Flare\Values\AutomationType;

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

    public function activeAutomation(Character $character): ?CharacterAutomation
    {
        return $character->currentAutomations()
            ->where('completed_at', '>', now())
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();
    }

    public function isBlocked(Character $character, string $action, ?Location $destinationLocation = null): bool
    {
        return ! is_null($this->blockedContext($character, $action, $destinationLocation));
    }

    public function blockedContext(Character $character, string $action, ?Location $destinationLocation = null): ?array
    {
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

    public function blockedMessage(CharacterAutomation $automation, ?string $action = null): string
    {
        $automationType = new AutomationType($automation->type);

        if ($action === self::START_FACTION_LOYALTY && ($automationType->isExploring() || $automationType->isDelve())) {
            $automationName = $this->automationName($automation);

            return 'You are currently doing ' . $automationName . '. This action cannot be completed right now. Please cancel ' . $automationName . ' first.';
        }

        return 'You cannot do that while ' . $this->automationName($automation) . ' automation is running. Cancel it first.';
    }

    public function isSpecialExplorationLocation(?Location $location): bool
    {
        if (is_null($location)) {
            return false;
        }

        return ! is_null($location->type) || ! is_null($location->enemy_strength_type);
    }

    private function automationBlocksAction(CharacterAutomation $automation, string $action, ?Location $destinationLocation = null): bool
    {
        $automationType = new AutomationType($automation->type);

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
            ]);
        }

        return $this->explorationBlocksAction($automation, $action, $destinationLocation);
    }

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

    private function automationName(CharacterAutomation $automation): string
    {
        $automationType = new AutomationType($automation->type);

        if ($automationType->isExploring()) {
            return 'Exploration';
        }

        if ($automationType->isDelve()) {
            return 'Delve';
        }

        return 'Faction Loyalty';
    }
}
