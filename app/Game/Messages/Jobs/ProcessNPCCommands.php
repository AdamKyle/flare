<?php

namespace App\Game\Messages\Jobs;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Npc;
use App\Flare\Models\User;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Handlers\NpcCommandHandler;
use App\Game\Skills\Services\DisenchantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessNPCCommands implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;

    private $npc;

    private $commandType;

    /**
     * @param Character $character
     * @param int $slotId
     * @param bool $isLastJob
     */
    public function __construct(User $user, Npc $npc, int $commandType)
    {
        $this->user        = $user;
        $this->npc         = $npc;
        $this->commandType = $commandType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(NpcCommandHandler $npcCommandHandler) {
        $npcCommandHandler->handleForType($this->commandType, $this->npc, $this->user);
    }
}
