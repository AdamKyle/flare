<?php

namespace App\Game\Kingdoms\Handlers;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Flare\Models\Character;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;

class UpdateKingdomHandler {

    private Manager $manager;

    private KingdomTransformer $kingdomTransformer;

    /**
     * @param Manager $manager
     * @param KingdomTransformer $kingdomTransformer
     */
    public function __construct(Manager $manager, KingdomTransformer $kingdomTransformer) {
        $this->manager            = $manager;
        $this->kingdomTransformer = $kingdomTransformer;
    }

    public function refreshPlayersKingdoms(Character $character) {
        $kingdoms = $character->kingdoms;

        $collection = new Collection($kingdoms, $this->kingdomTransformer);

        $kingdoms = $this->manager->createData($collection)->toArray();

        event(new UpdateKingdom($character->user,  $kingdoms));
    }
}
