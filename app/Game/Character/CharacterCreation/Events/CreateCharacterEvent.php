<?php

namespace App\Game\Character\CharacterCreation\Events;

use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;

class CreateCharacterEvent {

    use SerializesModels;

    /**
     * @var User $user
     */
    public User $user;

    /**
     * @var GameClass $class
     */
    public GameClass $class;

    /**
     * @var GameRace $race
     */
    public GameRace $race;

    /**
     * @var GameMap $map
     */
    public GameMap $map;

    /**
     * @var string $characterName
     */
    public string $characterName;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param GameMap $map
     * @param Request $request
     * @param string|null $characterName
     */
    public function __construct(User $user, GameMap $map, Request $request, string $characterName = null) {
        $this->user          = $user;
        $this->race          = GameRace::find($request->race);
        $this->class         = GameClass::find($request->class);
        $this->map           = $map;
        $this->characterName = !is_null($characterName) ? $characterName : $request->name;
    }
}
