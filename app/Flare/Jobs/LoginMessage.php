<?php

namespace App\Flare\Jobs;

use App\Flare\Models\Event;
use App\Game\Events\Values\EventType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Character;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class LoginMessage implements ShouldQueue {
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

        event(new GlobalMessageEvent('The Creator smiles as ' . $this->character->name . ' walks from the shadows of the abyss back into the land of Tlessa! Say Hi!'));

        $pvpEvent       = Event::where('type', EventType::MONTHLY_PVP)->first();
        $celestialEvent = Event::where('type', EventType::WEEKLY_CELESTIALS)->first();

        if (!is_null($pvpEvent)) {
            event(new ServerMessageEvent($user, 'Monthly pvp will begin tonight at 7pm GMT-6. Actions area has been updated to show a new button: Join PVP. Click this and follow the steps to be registered to participate. Registration will be open till 6:30pm GMT-6.'));
        }

        if (!is_null($celestialEvent)) {
            event(new ServerMessageEvent($user, 'Celestials have been set free till tomorrow at 1pm GMT-6. All you have to do is move around to watch them spawn (80% chance). Celestials Drop Valuable shards for Alchemy crafting!'));
        }
    }
}
