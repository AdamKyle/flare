<?php

namespace App\Game\Kingdoms\Handlers;


use App\Flare\Models\User;
use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Services\MovementService;

class GiveKingdomsToNpcHandler {

    private $movementService;

    public function __construct(MovementService $movementService) {
        $this->movementService = $movementService;
    }

    public function giveKingdoms(User $user) {
        $kingdoms = $user->character->kingdoms;

        if ($kingdoms->isEmpty()) {
            return;
        }

        foreach ($kingdoms as $kingdom) {
            $kingdom->character_id   = null;
            $kingdom->npc_owned      = true;
            $kingdom->current_morale = 0.01;

            $kingdom->save();
        }

        $map = $user->character->map;

        $this->movementService->processArea($user->character);

        broadcast(new UpdateMapDetailsBroadcast($map, $user, $this->movementService));
    }
}
