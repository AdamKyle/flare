<?php

namespace App\Flare\Jobs;

use App\Flare\Models\Character;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegisterMessage implements ShouldQueue
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

        event(new ServerMessageEvent($user, 'Welcome! I am so glad you could join us, child! What an exciting time to join a rapidly developing game.
         If you have questions, my fabulous friend, you can check out the "Help I am stuck" link, at the top. You can also click the version number to see release notes and past releases.
         There is even a discord icon up there. Finally you can ask for help here in the chat. Chat is loaded from the last 24 hours, so if someone doesn\'t respond right away, they will see your question on their next login!
          The Creator, me, also tends to answer people\'s questions on discord and will also answer people\'s questions here every so often throughout the day! Even if you logout and come back later, chances are someone (like me :)) answered it :D'));

        event(new GlobalMessageEvent('The skies open and the light shines through. The heavens have birthed forward a new hero of the land: '.$user->character->name.'. Please take a moment and say hello.'));
    }
}
