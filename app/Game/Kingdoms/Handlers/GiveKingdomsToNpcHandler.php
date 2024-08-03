<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Game\Core\Traits\KingdomCache;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Services\LocationService;

class GiveKingdomsToNpcHandler
{
    use KingdomCache;

    private LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Give a single kingdom to the NPC.
     */
    public function giveKingdomToNPC(Kingdom $kingdom): void
    {
        $character = $kingdom->character;

        $this->removeKingdomFromCache($character, $kingdom);

        $kingdom->update([
            'character_id' => null,
            'npc_owned' => true,
            'current_morale' => 0.01,
        ]);

        $map = $character->map;

        event(new UpdateMapDetailsBroadcast($map, $character->user, $this->locationService));

        event(new UpdateGlobalMap($character));
    }

    /**
     * Give all users kingdoms to the NPC.
     */
    public function giveKingdoms(User $user): void
    {
        $kingdoms = $user->character->kingdoms;

        if ($kingdoms->isEmpty()) {
            return;
        }

        foreach ($kingdoms as $kingdom) {

            $kingdom->update([
                'character_id' => null,
                'npc_owned' => true,
                'current_morale' => 0.01,
            ]);
        }

        $map = $user->character->map;

        event(new UpdateMapDetailsBroadcast($map, $user, $this->locationService));

        event(new UpdateGlobalMap($user->character));
    }
}
