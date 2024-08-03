<?php

namespace App\Flare\Jobs;

use App\Flare\Models\Character;
use App\Game\Maps\Events\UpdateDuelAtPosition;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveKilledInPvpFromUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Character $character;

    /**
     * Create a new job instance.
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }

    public function handle()
    {

        $this->character->update([
            'killed_in_pvp' => false,
        ]);

        event(new UpdateDuelAtPosition($this->character->user));

        event(new ServerMessageEvent($this->character->user, 'You pvp safety net has ended. Your location will show again in chat.'));
    }
}
