<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GemBagSlot;
use App\Flare\Transformers\CharacterGemSlotsTransformer;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class CharacterGemBagService
{
    use ResponseBuilder;

    private Manager $manager;

    private CharacterGemsTransformer $gemsTransformer;

    private CharacterGemSlotsTransformer $characterGemBagTransformer;

    public function __construct(Manager $manager, CharacterGemSlotsTransformer $characterGemBagTransformer, CharacterGemsTransformer $gemsTransformer)
    {
        $this->manager = $manager;
        $this->gemsTransformer = $gemsTransformer;
        $this->characterGemBagTransformer = $characterGemBagTransformer;
    }

    /**
     * Get gems from character bag.
     */
    public function getGems(Character $character): array
    {
        $gems = new Collection($character->gemBag->gemSlots, $this->characterGemBagTransformer);
        $gems = $this->manager->createData($gems)->toArray();

        return $this->successResult($gems);
    }

    public function getGemData(Character $character, GemBagSlot $gemSlot): array
    {

        if ($character->id !== $gemSlot->gemBag->character_id) {
            return $this->errorResult('No. Not yours!');
        }

        $gem = new Item($gemSlot->gem, $this->gemsTransformer);
        $gem = $this->manager->createData($gem)->toArray();

        return $this->successResult(['gem' => $gem]);
    }
}
