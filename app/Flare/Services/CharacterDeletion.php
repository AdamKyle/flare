<?php

namespace App\Flare\Services;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GemBag;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\Inventory;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\RaidBossParticipation;
use App\Flare\Models\SmeltingProgress;
use App\Flare\Models\User;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use Illuminate\Support\Collection;

class CharacterDeletion
{
    public function __construct(
        private readonly GiveKingdomsToNpcHandler $giveKingdomsToNpcHandler,
        private readonly CharacterCreationPipeline $characterCreationPipeline,
        private readonly CharacterBuildState $characterBuildState,
    ) {}

    public function deleteCharacterFromUser(Character $character, array $params = [])
    {
        $user = $character->user;

        foreach ($character->kingdoms as $kingdom) {
            $this->giveKingdomsToNpcHandler->giveKingdomToNPC($kingdom);
        }

        if (! is_null($character->inventory)) {
            $this->emptyCharacterInventory($character->inventory);
        }

        if (! is_null($character->gemBag)) {
            $this->emptyCharacterGemBag($character->gemBag);
        }

        if (! is_null($character->inventorySets)) {
            $this->emptyCharacterInventorySets($character->inventorySets);
        }

        $this->deleteCharacterMarketListings($character);

        $this->deleteCharacter($character);

        if (! empty($params)) {
            $this->createCharacter($user->refresh(), $params);
        }
    }

    protected function createCharacter(User $user, array $params): void
    {
        $race = GameRace::find($params['race_id']);
        $class = GameClass::find($params['class_id']);
        $map = GameMap::where('default', true)->first();

        $this->characterBuildState
            ->setUser($user)
            ->setRace($race)
            ->setClass($class)
            ->setMap($map)
            ->setCharacterName($params['name'])
            ->setNow(now());

        $this->characterCreationPipeline->run($this->characterBuildState);

        $user->refresh()->update([
            'guide_enabled' => $params['guide'],
        ]);
    }

    protected function removeMercenaries(Collection $mercenaries): void
    {
        foreach ($mercenaries as $merc) {
            $merc->delete();
        }
    }

    protected function deleteCharacterMarketListings(Character $character): void
    {

        MarketBoard::where('character_id', $character->id)->chunkById(250, function ($marketListings) {
            foreach ($marketListings as $marketListing) {
                $marketListing->delete();
            }
        });
    }

    protected function emptyCharacterInventory(Inventory $inventory): void
    {
        foreach ($inventory->slots as $slot) {
            $slot->delete();
        }

        $inventory->delete();
    }

    protected function emptyCharacterGemBag(GemBag $gemBag): void
    {
        foreach ($gemBag->gemSlots as $slot) {
            $slot->delete();
        }

        $gemBag->delete();
    }

    protected function emptyCharacterInventorySets(Collection $inventorySets): void
    {
        foreach ($inventorySets as $set) {
            foreach ($set->slots as $slot) {
                $slot->delete();
            }

            $set->delete();
        }
    }

    protected function deleteCharacter(Character $character): void
    {
        CharacterInCelestialFight::where('character_id', $character->id)->delete();

        ExplorationLog::where('character_id', $character->id)->delete();
        ExplorationWarning::where('character_id', $character->id)->delete();

        DelveLog::where('character_id', $character->id)->delete();
        DelveExploration::where('character_id', $character->id)->delete();

        RaidBossParticipation::where('character_id', $character->id)->delete();

        $this->deleteRaidParticipations($character);

        $character->globalEventKills()->delete();
        $character->globalEventCrafts()->delete();
        $character->globalEventEnchants()->delete();
        $character->globalEventParticipation()->delete();

        $this->deleteGlobalEventCraftingInventories($character);

        SmeltingProgress::where('character_id', $character->id)->delete();

        $character->weeklyBattleFights()->delete();

        BuildingExpansionQueue::where('character_id', $character->id)->delete();

        CapitalCityBuildingCancellation::where('character_id', $character->id)->delete();
        CapitalCityBuildingQueue::where('character_id', $character->id)->delete();

        CapitalCityUnitCancellation::where('character_id', $character->id)->delete();
        CapitalCityUnitQueue::where('character_id', $character->id)->delete();

        FactionLoyaltyAutomationWarning::where('character_id', $character->id)->delete();
        $this->deleteFactionLoyaltyAutomations($character);

        $character->skills()->delete();

        $character->kingdomAttackLogs()->delete();

        $character->unitMovementQueues()->delete();

        $character->boons()->delete();

        $character->questsCompleted()->delete();

        $character->currentAutomations()->delete();

        $character->factions()->delete();

        foreach ($character->factionLoyalties as $loyalty) {

            foreach ($loyalty->factionLoyaltyNpcs() as $factionNpc) {

                $factionNpc->factionLoyaltyNpcTasks()->delete();
            }

            $loyalty->factionLoyaltyNpcs()->delete();

            $loyalty->delete();
        }

        $character->passiveSkills()->delete();

        $this->deleteClassRanks($character);

        $character->classRanks()->delete();

        $character->classSpecialsEquipped()->delete();

        $character->map()->delete();

        $character->delete();
    }

    protected function deleteClassRanks(Character $character): void
    {
        foreach ($character->classRanks as $classRank) {
            $classRank->weaponMasteries()->delete();
            $classRank->delete();
        }
    }

    protected function deleteRaidParticipations(Character $character): void
    {
        \DB::table('raid_participations')->where('character_id', $character->id)->delete();
    }

    protected function deleteGlobalEventCraftingInventories(Character $character): void
    {
        GlobalEventCraftingInventory::where('character_id', $character->id)
            ->chunkById(100, function ($inventories) {
                foreach ($inventories as $inventory) {
                    $inventory->craftingSlots()->delete();
                    $inventory->delete();
                }
            });
    }

    protected function deleteFactionLoyaltyAutomations(Character $character): void
    {
        FactionLoyaltyAutomation::where('character_id', $character->id)
            ->chunkById(100, function ($automations) {
                foreach ($automations as $automation) {
                    $automation->log()->delete();
                    $automation->delete();
                }
            });
    }
}
