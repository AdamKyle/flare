<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Admin\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\User;

class CreateCharacterEvent
{
    use SerializesModels;

    /**
     * The user.
     *
     * @var \App\User
     */
    public $user;

    public $class;

    public $race;

    public $characterName;

    public $map;

    /**
     * Create a new event instance.
     *
     * @param  \App\User $user
     * @return void
     */
    public function __construct(User $user, GameMap $map, Request $request)
    {
        $this->user          = $user;
        $this->race          = GameRace::find($request->race);
        $this->class         = GameClass::find($request->class);
        $this->map           = $map;
        $this->characterName = $request->name;
    }
}
