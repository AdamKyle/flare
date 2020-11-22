<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\User;

class CreateCharacterEvent
{
    use SerializesModels;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var GameClass $class
     */
    public $class;

    /**
     * @var GameRace $race
     */
    public $race;

    /**
     * @var string $characterName
     */
    public $characterName;

    /**
     * @var GameMap $map
     */
    public $map;

    /**
     * Create a new event instance.
     *
     * @param  User $user
     * @param GameMap $map
     * @param Request $request
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
