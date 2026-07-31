<?php

namespace App\Game\Tops\Listeners;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Tops\Events\CharacterTopsInspectionUpdated;
use App\Game\Tops\Services\BroadcastTopsUpdateService;
use App\Game\Tops\Services\CharacterTopsInspectionService;

class CharacterTopsUpdateListener
{
    public function __construct(
        private readonly BroadcastTopsUpdateService $broadcastTopsUpdateService,
        private readonly CharacterTopsInspectionService $characterTopsInspectionService,
    ) {}

    public function handle(UpdateTopBarEvent $event): void
    {
        $this->broadcastTopsUpdateService->broadcastCharacterCurrentMonth();
        broadcast(new CharacterTopsInspectionUpdated($event->character->id, $this->characterTopsInspectionService->fullProfile($event->character)));
    }
}
