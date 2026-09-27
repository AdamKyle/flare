<?php

namespace App\Game\Automation\Delve\Services;

use App\Flare\Models\Character;
use App\Game\Automation\Delve\Events\DelveStatusUpdated;

class DelveStatusBroadcastService
{
    /**
     * @param DelveStatusService $delveStatusService
     */
    public function __construct(private readonly DelveStatusService $delveStatusService) {}

    /**
     * Broadcast the Character's current Delve status snapshot to their private Delve channel.
     *
     * @param Character $character
     * @return void
     */
    public function broadcast(Character $character): void
    {
        $status = $this->delveStatusService->statusForCharacter($character);

        event(new DelveStatusUpdated($character->user_id, $status));
    }
}
