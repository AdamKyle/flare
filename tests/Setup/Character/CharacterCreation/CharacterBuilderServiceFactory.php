<?php

namespace Tests\Setup\Character\CharacterCreation;

use App\Flare\Models\Character;
use Illuminate\Support\Str;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CharacterBuilderServiceFactory
{
    use CreateCharacter, CreateClass, CreateRace, CreateUser;

    public function makeBareCharacter(?string $className = 'Fighter'): Character
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass(['name' => $className]);

        return $this->createCharacter([
            'damage_stat' => $class->damage_stat,
            'name' => Str::random(10),
            'user_id' => $user->id,
            'level' => 1,
            'xp' => 0,
            'can_attack' => true,
            'can_move' => true,
            'inventory_max' => 75,
            'gold' => 10,
            'game_class_id' => $class->id,
            'game_race_id' => $race->id,
        ]);
    }
}
