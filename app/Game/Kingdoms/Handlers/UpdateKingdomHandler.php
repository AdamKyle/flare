<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Character;
use App\Game\Kingdoms\Events\UpdateKingdomTable;
use App\Game\Kingdoms\Transformers\KingdomTableTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class UpdateKingdomHandler
{
    private Manager $manager;

    private KingdomTableTransformer $kingdomTableTransformer;

    public function __construct(Manager $manager, KingdomTableTransformer $kingdomTableTransformer)
    {
        $this->manager = $manager;
        $this->kingdomTableTransformer = $kingdomTableTransformer;
    }

    public function refreshPlayersKingdoms(Character $character)
    {
        $kingdoms = $character->kingdoms()->orderByDesc('is_capital')->orderBy('game_map_id')->orderBy('id')->get();

        $collection = new Collection($kingdoms, $this->kingdomTableTransformer);

        $kingdoms = $this->manager->createData($collection)->toArray();

        event(new UpdateKingdomTable($character->user, $kingdoms));
    }
}
