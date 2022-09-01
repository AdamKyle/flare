<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom as UpdateKingdomDetails;

class UpdateKingdom {

    /**
     * @var KingdomTransformer $kingdomTransformer
     */
    private KingdomTransformer $kingdomTransformer;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @param KingdomTransformer $kingdomTransformer
     * @param Manager $manager
     */
    public function __construct(KingdomTransformer $kingdomTransformer, Manager $manager) {
        $this->kingdomTransformer = $kingdomTransformer;
        $this->manager            = $manager;
    }

    /**
     * @param Kingdom $kingdom
     * @return void
     */
    public function updateKingdom(Kingdom $kingdom): void {
        $character = $kingdom->character;

        $kingdom = new Item($kingdom, $this->kingdomTransformer);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdomDetails($character->user, $kingdom));
    }

    /**
     * Updates all the characters kingdoms.
     *
     * @param Character $character
     * @return void
     */
    public function updateKingdomAllKingdoms(Character $character): void {
        $kingdomData = new Collection($character->kingdoms, $this->kingdomTransformer);

        $kingdomData = $this->manager->createData($kingdomData)->toArray();

        event(new UpdateKingdomDetails($character->user, $kingdomData));
    }
}
