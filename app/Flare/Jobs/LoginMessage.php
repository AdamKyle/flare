<?php

namespace App\Flare\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Character;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class LoginMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * Create a new job instance.
     *
     * @param Collection $characters
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }

    public function handle() {

        $user = $this->character->user;

        event(new ServerMessageEvent($user, 'Well hello there and welcome back my friend. I hope you are having a fantastic day. If you need help you can ask in chat and someone will see it and respond as soon as possible.
            I am so glad you came back :D'));

        event(new GlobalMessageEvent('There creator smiles as ' . $this->character->name . ' walks from the shadows of the abyss back into the land of Tlessa! Say Hi!'));
    }
}
