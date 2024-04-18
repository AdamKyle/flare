<?php

namespace App\Game\Kingdoms\Handlers;

use App\Game\Kingdoms\Events\UpdateKingdomTable;
use App\Game\Kingdoms\Transformers\KingdomTableTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Flare\Models\Character;

class UpdateKingdomHandler {

    private Manager $manager;

    private KingdomTableTransformer $kingdomTableTransformer;

    /**
     * @param Manager $manager
     * @param KingdomTableTransformer $kingdomTableTransformer
     */
    public function __construct(Manager $manager, KingdomTableTransformer $kingdomTableTransformer) {
        $this->manager            = $manager;
        $this->kingdomTableTransformer = $kingdomTableTransformer;
    }

    public function refreshPlayersKingdoms(Character $character) {
        $kingdoms = $character->kingdoms;

        $collection = new Collection($kingdoms, $this->kingdomTableTransformer);

        $kingdoms = $this->manager->createData($collection)->toArray();

        event(new UpdateKingdomTable($character->user,  $kingdoms));
    }
}
