<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Events\UpdateKingdom as UpdateKingdomDetails;
use App\Game\Kingdoms\Events\UpdateKingdomLogs;
use App\Game\Kingdoms\Events\UpdateKingdomQueues;
use App\Game\Kingdoms\Events\UpdateKingdomTable;
use App\Game\Kingdoms\Transformers\KingdomAttackLogsTransformer;
use App\Game\Kingdoms\Transformers\KingdomTableTransformer;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class UpdateKingdom
{
    private KingdomTransformer $kingdomTransformer;

    private KingdomTableTransformer $kingdomTableTransformer;

    private KingdomAttackLogsTransformer $kingdomAttackLogsTransformer;

    private Manager $manager;

    public function __construct(
        KingdomTransformer $kingdomTransformer,
        KingdomTableTransformer $kingdomTableTransformer,
        KingdomAttackLogsTransformer $kingdomAttackLogsTransformer,
        Manager $manager
    ) {
        $this->kingdomTransformer = $kingdomTransformer;
        $this->kingdomTableTransformer = $kingdomTableTransformer;
        $this->kingdomAttackLogsTransformer = $kingdomAttackLogsTransformer;
        $this->manager = $manager;
    }

    public function updateKingdom(Kingdom $kingdom): void
    {
        $character = $kingdom->character;

        $kingdomData = new Item($kingdom, $this->kingdomTransformer);

        $kingdomData = $this->manager->createData($kingdomData)->toArray();

        event(new UpdateKingdomDetails($character->user, $kingdomData));

        event(new UpdateKingdomQueues($kingdom));
    }

    /**
     * Updates all the characters kingdoms.
     */
    public function updateKingdomAllKingdoms(Character $character): void
    {
        $kingdoms = $character->kingdoms()->orderByDesc('is_capital')->orderBy('game_map_id')->orderBy('id')->get();

        $kingdomData = new Collection($kingdoms, $this->kingdomTableTransformer);

        $kingdomData = $this->manager->createData($kingdomData)->toArray();

        event(new UpdateKingdomTable($character->user, $kingdomData));
    }

    /**
     * Updates kingdom attack logs for a character.
     */
    public function updateKingdomLogs(Character $character, bool $setCharacterId = false): void
    {
        $logs = KingdomLog::where('character_id', $character->id)->orderBy('id', 'desc')->get();

        $transformer = $this->kingdomAttackLogsTransformer;

        if ($setCharacterId) {
            $transformer = $transformer->setCharacterId($character->id);
        }

        $logData = new Collection($logs, $transformer);

        $logData = $this->manager->createData($logData)->toArray();

        event(new UpdateKingdomLogs($character, $logData));
    }
}
