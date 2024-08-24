<?php

namespace App\Game\Character\CharacterCreation\Events;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\User;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class CreateCharacterEvent
{
    use SerializesModels;

    public User $user;

    public GameClass $class;

    public GameRace $race;

    public GameMap $map;

    public string $characterName;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, GameMap $map, Request $request, ?string $characterName = null)
    {
        $this->user = $user;
        $this->race = GameRace::find($request->race);
        $this->class = GameClass::find($request->class);
        $this->map = $map;
        $this->characterName = ! is_null($characterName) ? $characterName : $request->name;
    }
}
