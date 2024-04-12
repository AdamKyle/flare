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

class CharacterGemBagService {

    use ResponseBuilder;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var CharacterGemsTransformer $gemsTransformer
     */
    private CharacterGemsTransformer $gemsTransformer;

    /**
     * @var CharacterGemSlotsTransformer $characterGemBagService
     */
    private CharacterGemSlotsTransformer $characterGemBagTransformer;

    /**
     * @param Manager $manager
     * @param CharacterGemSlotsTransformer $characterGemBagTransformer
     * @param CharacterGemsTransformer $gemsTransformer
     */
    public function __construct(Manager $manager, CharacterGemSlotsTransformer $characterGemBagTransformer, CharacterGemsTransformer $gemsTransformer) {
        $this->manager                    = $manager;
        $this->gemsTransformer            = $gemsTransformer;
        $this->characterGemBagTransformer = $characterGemBagTransformer;
    }

    /**
     * Get gems from character bag.
     *
     * @param Character $character
     * @return array
     */
    public function getGems(Character $character): array {
        $gems = new Collection($character->gemBag->gemSlots, $this->characterGemBagTransformer);
        $gems = $this->manager->createData($gems)->toArray();

        return $this->successResult(['gem_slots' => $gems]);
    }

    /**
     * @param Character $character
     * @param GemBagSlot $gemSlot
     * @return array
     */
    public function getGemData(Character $character, GemBagSlot $gemSlot): array {

        if ($character->id !== $gemSlot->gemBag->character_id) {
            return $this->errorResult('No. Not yours!');
        }

        $gem = new Item($gemSlot->gem, $this->gemsTransformer);
        $gem = $this->manager->createData($gem)->toArray();

        return $this->successResult(['gem' => $gem]);
    }
}
