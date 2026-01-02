<?php

namespace App\Game\Battle\Jobs;

use App\Flare\Models\Character;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CharacterUpdateJob implements ShouldQueue
{
    use Dispatchable, FetchEquipped, InteractsWithQueue, Queueable, SerializesModels;

    private Character $character;

    public function __construct(Character $character)
    {
        $this->character = $character;
    }

    /**
     * Handles Character Top Bar Update.
     */
    public function handle(): void
    {
        event(new UpdateCharacterBaseDetailsEvent($this->character));

        event(new UpdateCharacterCurrenciesEvent($this->character));
    }
}
