<?php

namespace App\Game\Battle\Jobs;

use App\Flare\Models\Character;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CharacterUpdateJob implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FetchEquipped;

    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @param Character $character
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }

    /**
     * Handles Character Top Bar Update.
     *
     * @return void
     */
    public function handle(): void {
        event(new UpdateTopBarEvent($this->character));

        event(new UpdateCharacterCurrenciesEvent($this->character));
    }
}
