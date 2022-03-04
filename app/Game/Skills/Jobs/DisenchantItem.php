<?php

namespace App\Game\Skills\Jobs;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Events\ShowTimeOutEvent;
use Illuminate\Support\Facades\Cache;

class DisenchantItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var int $slotId
     */
    private $slotId;

    /**
     * @var bool $isLastJob
     */
    private $isLastJob;

    /**
     * @param Character $character
     * @param int $slotId
     * @param bool $isLastJob
     */
    public function __construct(Character $character, int $slotId, bool $isLastJob = false)
    {
        $this->character = $character;
        $this->slotId    = $slotId;
        $this->isLastJob = $isLastJob;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(DisenchantService $service) {
        $slot = InventorySlot::find($this->slotId);

        if (!is_null($slot)) {
            $service->disenchantWithSkill($this->character, $slot);
            $hasCache = Cache::has('character-' . $this->character->id);

            if ($hasCache) {
                $currentAmount = Cache::get('character-' . $this->character->id);
                Cache::put('character-' . $this->character->id, $service->getGoldDust() + $currentAmount);
            } else {
                Cache::put('character-' . $this->character->id, $service->getGoldDust());
            }
        }

        if ($this->isLastJob) {
            $goldDust = Cache::pull('character-' . $this->character->id);

            event(new ServerMessageEvent($this->character->user, 'You gained a total of: ' . $goldDust . ' Gold Dust from disenchanting. This does not include Gold Dust Rushes.'));
        }

        event(new CharacterInventoryUpdateBroadCastEvent($this->character->user, 'inventory'));

        event(new UpdateTopBarEvent($this->character->refresh()));
    }
}
