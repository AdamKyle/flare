<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\Kingdom;
use App\Flare\Models\MarketBoard;
use App\Flare\Transformers\MarketItemsTransformer;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use League\Fractal\Manager;

class CharacterDeletion {

    private KingdomResourcesService $kingdomResourcesService;

    public function __construct(KingdomResourcesService $kingdomResourcesService) {
        $this->kingdomResourcesService = $kingdomResourcesService;
    }

    public function deleteCharacterFromUser(Character $character) {
        if (!is_null($character->inventory)) {
            $this->emptyCharacterInventory($character->inventory);
        }

        if (!$character->inventorySets->isEmpty()) {
            $this->emptyCharacterInventorySets($character->inventorySets);
        }

        $this->deleteCharacterMarketListings($character);

        foreach ($character->kingdoms as $kingdom) {
            $this->kingdomResourcesService->setKingdom($kingdom)->giveNPCKingdoms(false, true);
        }

        $character->skills()->delete();

        $this->deleteCharacter($character);
    }

    protected function deleteCharacterMarketListings(Character $character) {

        MarketBoard::where('character_id', $character->id)->chunkById(250, function($marketListings) {
            foreach ($marketListings as $marketListing) {
                $marketListing->delete();
            }
        });
    }

    protected function emptyCharacterInventory(Inventory $inventory) {
        foreach ($inventory->slots as $slot) {
            $slot->delete();
        }

        $inventory->delete();
    }

    protected function emptyCharacterInventorySets(Collection $inventorySets) {
        foreach ($inventorySets as $set) {
            foreach ($set->slots as $slot) {
                $slot->delete();
            }

            $set->delete();
        }
    }


    protected function deleteCharacter(Character $character) {
        $character->skills()->delete();

        $character->kingdomAttackLogs()->delete();

        $character->unitMovementQueues()->delete();

        $character->boons()->delete();

        $character->questsCompleted()->delete();

        $character->currentAutomations()->delete();

        $character->factions()->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $character->passiveSkills()->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $character->map()->delete();

        $character->delete();
    }
}
