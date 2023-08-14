<?php

namespace App\Game\Battle\Jobs;

use Illuminate\Bus\Queueable;
use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Game\Core\Events\UpdateTopBarEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;

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
