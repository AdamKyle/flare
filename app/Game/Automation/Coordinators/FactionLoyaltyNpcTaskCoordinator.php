<?php

namespace App\Game\Automation\Coordinators;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty as FactionLoyaltyModel;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty as FactionLoyaltyConcern;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\TraverseService;
use Exception;

class FactionLoyaltyNpcTaskCoordinator
{
    use FactionLoyaltyConcern;

    private Character $character;

    private FactionLoyaltyAutomation $factionLoyaltyAutomation;

    private bool $shouldEndAutomation = false;

    private bool $skippedUnmaxedFactionWithIncompleteTasks = false;

    public function __construct(
        private readonly FactionLoyaltyService $factionLoyaltyService,
        private readonly MovementService $movementService,
        private readonly TraverseService $traverseService,
    ) {}

    /**
     * Set up the coordinator.
     */
    public function setUp(Character $character, FactionLoyaltyAutomation $factionLoyaltyAutomation): FactionLoyaltyNpcTaskCoordinator
    {
        $this->character = $character;
        $this->factionLoyaltyAutomation = $factionLoyaltyAutomation;
        $this->shouldEndAutomation = false;
        $this->skippedUnmaxedFactionWithIncompleteTasks = false;

        return $this;
    }

    /**
     * Resolve the next NPC to assist.
     *
     * @throws Exception
     */
    public function resolveNpc(): ?FactionLoyaltyNpc
    {
        $currentFactionLoyaltyNpc = $this->factionLoyaltyAutomation->factionLoyaltyNpc;

        if ($this->hasIncompleteTasks($currentFactionLoyaltyNpc)) {
            return $currentFactionLoyaltyNpc;
        }

        $sameMapFactionLoyaltyNpc = $this->findSameMapNpcWithIncompleteTasks($currentFactionLoyaltyNpc);

        if (! is_null($sameMapFactionLoyaltyNpc)) {
            return $this->switchToNpc($sameMapFactionLoyaltyNpc, $this->getSameMapSwitchMessage($currentFactionLoyaltyNpc, $sameMapFactionLoyaltyNpc));
        }

        $existingFactionLoyaltyNpc = $this->findExistingFactionLoyaltyNpcWithIncompleteTasks($currentFactionLoyaltyNpc);

        if (! is_null($existingFactionLoyaltyNpc)) {
            return $this->travelPledgeAndAssist($existingFactionLoyaltyNpc);
        }

        $newFactionLoyaltyNpc = $this->findNewFactionLoyaltyNpcWithIncompleteTasks();

        if (! is_null($newFactionLoyaltyNpc)) {
            return $newFactionLoyaltyNpc;
        }

        $this->shouldEndAutomation = true;

        $this->sendOutEventLogUpdate($this->getNoAvailableFactionMessage(), true);

        return null;
    }

    /**
     * Should the automation end?
     */
    public function shouldEndAutomation(): bool
    {
        return $this->shouldEndAutomation;
    }

    /**
     * Find an NPC on the same map with incomplete tasks.
     */
    private function findSameMapNpcWithIncompleteTasks(FactionLoyaltyNpc $currentFactionLoyaltyNpc): ?FactionLoyaltyNpc
    {
        $factionLoyaltyNpcs = $currentFactionLoyaltyNpc->factionLoyalty
            ->factionLoyaltyNpcs()
            ->with(['factionLoyaltyNpcTasks', 'npc'])
            ->where('id', '!=', $currentFactionLoyaltyNpc->id)
            ->get();

        foreach ($factionLoyaltyNpcs as $factionLoyaltyNpc) {
            if ($this->hasIncompleteTasks($factionLoyaltyNpc)) {
                return $factionLoyaltyNpc;
            }
        }

        return null;
    }

    /**
     * Find an existing faction loyalty NPC with incomplete tasks.
     */
    private function findExistingFactionLoyaltyNpcWithIncompleteTasks(FactionLoyaltyNpc $currentFactionLoyaltyNpc): ?FactionLoyaltyNpc
    {
        $factionLoyalties = $this->character
            ->factionLoyalties()
            ->with(['faction.gameMap', 'factionLoyaltyNpcs.factionLoyaltyNpcTasks', 'factionLoyaltyNpcs.npc'])
            ->where('id', '!=', $currentFactionLoyaltyNpc->faction_loyalty_id)
            ->get();

        foreach ($factionLoyalties as $factionLoyalty) {
            $factionLoyaltyNpc = $this->findNpcWithIncompleteTasks($factionLoyalty);

            if (is_null($factionLoyaltyNpc)) {
                continue;
            }

            if (! $this->canPledgeToFaction($factionLoyalty->faction)) {
                continue;
            }

            if (! $this->canTravelToFaction($factionLoyalty->faction)) {
                continue;
            }

            return $factionLoyaltyNpc;
        }

        return null;
    }

    /**
     * Find a new faction loyalty NPC with incomplete tasks.
     */
    private function findNewFactionLoyaltyNpcWithIncompleteTasks(): ?FactionLoyaltyNpc
    {
        $existingFactionIds = $this->character->factionLoyalties()->pluck('faction_id')->toArray();

        $factions = $this->character
            ->factions()
            ->with('gameMap')
            ->where('maxed', true)
            ->whereNotIn('id', $existingFactionIds)
            ->get();

        foreach ($factions as $faction) {
            if (! $this->canTravelToFaction($faction)) {
                continue;
            }

            $factionLoyaltyNpc = $this->travelPledgeAndFindNpc($faction);

            if (! is_null($factionLoyaltyNpc)) {
                return $this->assistNpcAfterMapChange($factionLoyaltyNpc);
            }
        }

        return null;
    }

    /**
     * Find an NPC with incomplete tasks.
     */
    private function findNpcWithIncompleteTasks(FactionLoyaltyModel $factionLoyalty): ?FactionLoyaltyNpc
    {
        foreach ($factionLoyalty->factionLoyaltyNpcs as $factionLoyaltyNpc) {
            if ($this->hasIncompleteTasks($factionLoyaltyNpc)) {
                return $factionLoyaltyNpc;
            }
        }

        return null;
    }

    /**
     * Travel, pledge, and assist an existing NPC.
     *
     * @throws Exception
     */
    private function travelPledgeAndAssist(FactionLoyaltyNpc $factionLoyaltyNpc): ?FactionLoyaltyNpc
    {
        $faction = $factionLoyaltyNpc->factionLoyalty->faction;

        if (! $this->travelToFaction($faction)) {
            return null;
        }

        $pledgeResult = $this->factionLoyaltyService->pledgeLoyalty($this->character->refresh(), $faction);

        if ($pledgeResult['status'] !== 200) {
            return null;
        }

        return $this->assistNpcAfterMapChange($factionLoyaltyNpc->refresh());
    }

    /**
     * Travel, pledge, and find the first incomplete NPC.
     *
     * @throws Exception
     */
    private function travelPledgeAndFindNpc(Faction $faction): ?FactionLoyaltyNpc
    {
        if (! $this->travelToFaction($faction)) {
            return null;
        }

        $pledgeResult = $this->factionLoyaltyService->pledgeLoyalty($this->character->refresh(), $faction);

        if ($pledgeResult['status'] !== 200) {
            return null;
        }

        $factionLoyalty = $this->character
            ->refresh()
            ->factionLoyalties()
            ->with(['faction.gameMap', 'factionLoyaltyNpcs.factionLoyaltyNpcTasks', 'factionLoyaltyNpcs.npc'])
            ->where('faction_id', $faction->id)
            ->first();

        if (is_null($factionLoyalty)) {
            return null;
        }

        return $this->findNpcWithIncompleteTasks($factionLoyalty);
    }

    /**
     * Assist an NPC after changing maps.
     */
    private function assistNpcAfterMapChange(FactionLoyaltyNpc $factionLoyaltyNpc): FactionLoyaltyNpc
    {
        $faction = $factionLoyaltyNpc->factionLoyalty->faction;

        return $this->switchToNpc(
            $factionLoyaltyNpc,
            'You have traveled to '.$faction->gameMap->name.', pledged to that faction, and are now assisting '.$factionLoyaltyNpc->npc->real_name.'.'
        );
    }

    /**
     * Switch assistance to an NPC.
     */
    private function switchToNpc(FactionLoyaltyNpc $factionLoyaltyNpc, string $message): FactionLoyaltyNpc
    {
        $this->factionLoyaltyService->assistNpc($this->character->refresh(), $factionLoyaltyNpc);

        $this->factionLoyaltyAutomation->update([
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
        ]);

        $this->factionLoyaltyAutomation = $this->factionLoyaltyAutomation->refresh();

        $this->sendOutEventLogUpdate($message, true);

        return $factionLoyaltyNpc->refresh();
    }

    /**
     * Can the character pledge to the faction?
     */
    private function canPledgeToFaction(Faction $faction): bool
    {
        if ($faction->maxed) {
            return true;
        }

        $this->skippedUnmaxedFactionWithIncompleteTasks = true;

        return false;
    }

    /**
     * Can the character travel to the faction map?
     */
    private function canTravelToFaction(Faction $faction): bool
    {
        return $this->traverseService->canTravel($faction->game_map_id, $this->character);
    }

    /**
     * Travel to the faction map.
     */
    private function travelToFaction(Faction $faction): bool
    {
        $result = $this->movementService->updateCharacterPlane($faction->game_map_id, $this->character);

        if ($result['status'] !== 200) {
            return false;
        }

        $this->character = $this->character->refresh();

        return true;
    }

    /**
     * Get same map switch message.
     */
    private function getSameMapSwitchMessage(FactionLoyaltyNpc $currentFactionLoyaltyNpc, FactionLoyaltyNpc $nextFactionLoyaltyNpc): string
    {
        return 'You have completed all tasks for '.$currentFactionLoyaltyNpc->npc->real_name.'. You are now assisting '.$nextFactionLoyaltyNpc->npc->real_name.'.';
    }

    /**
     * Get the no available faction message.
     */
    private function getNoAvailableFactionMessage(): string
    {
        if ($this->skippedUnmaxedFactionWithIncompleteTasks) {
            return 'There are no other factions for you to pledge to. You have not maxed out other factions on other maps.';
        }

        return 'No incomplete faction loyalty tasks were found for any available NPC. Automation has ended.';
    }

    /**
     * Send the automation log update.
     */
    private function sendOutEventLogUpdate(string $message, bool $makeItalic = false, bool $isReward = false): void
    {
        if ($this->character->isLoggedIn()) {
            event(new AutomationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward));
        }
    }
}
