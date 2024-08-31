<?php

namespace App\Game\Character\CharacterInventory\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use App\Game\Character\CharacterInventory\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\DisenchantService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class DisenchantMany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @param array $slotIds
     */
    public function __construct(protected readonly Character $character, protected readonly array $slotIds) {
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(DisenchantService $disenchantService) {
        $character = $this->character;

        foreach ($this->slotIds as $slotId) {

            $foundItem = $this->character->inventory->slots()->find($slotId);

            if (is_null($foundItem)) {
                event(new ServerMessageEvent($this->character->user, 'Not all items you selected could be disenchanted. Something went wrong. We could not find one of the items in your inventory. File a bug report.'));

                Cache::delete('character-slots-to-disenchant-' . $this->character->id);

                throw new Exception($this->character->name . ' does not have an inventory slot to disenchant for: ' . $slotId);
            }

            $result = $disenchantService->disenchantItem($character, $foundItem->item, true);

            if ($result['status'] === 422) {
                event(new ServerMessageEvent($this->character->user, 'Something went wrong disenchanting: ' . $result['message']));

                Cache::delete('character-slots-to-disenchant-' . $this->character->id);

                throw new Exception('Something went wrong trying to disenchant: ' . $slotId. ' for character: ' . $this->character->name . ' message: ' . $result['message']);
            }

            $character = $this->character->refresh();
        }

        Cache::delete('character-slots-to-disenchant-' . $this->character->id);

        event(new CharacterInventoryUpdateBroadCastEvent($character->user, 'inventory'));

        event(new ServerMessageEvent($character->user, 'All done (Disenchanting selected items). You may manage your inventory as normal now.'));
    }
}
