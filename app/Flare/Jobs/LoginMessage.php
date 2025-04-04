<?php

namespace App\Flare\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LoginMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Character $character;

    /**
     * Create a new job instance.
     *
     * @param  Collection  $characters
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }

    public function handle()
    {

        $user = $this->character->user;

        event(new ServerMessageEvent($user, 'Well hello there and welcome back my friend. I hope you are having a fantastic day. If you need help you can ask in chat and someone will see it and respond as soon as possible.
            I am so glad you came back :D'));

        event(new GlobalMessageEvent('The Creator smiles as ' . $this->character->name . ' walks from the shadows of the abyss back into the land of Tlessa! Say Hi!'));

        $celestialEvent = Event::where('type', EventType::WEEKLY_CELESTIALS)->first();

        if (! is_null($celestialEvent)) {
            $endTime = Carbon::parse($celestialEvent->ends_at)->setTimeFrom(env('TIME_ZONE'))->format('g A T');
            event(new ServerMessageEvent($user, 'Celestials have been set free till tomorrow at: '.$endTime.'. All you have to do is move around to watch them spawn (80% chance). Celestials Drop Valuable shards for Alchemy crafting!'));
        }
    }
}
