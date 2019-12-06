<?php

namespace App\Flare\Builders;

use App\User;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\BaseStatValue;
use App\Flare\Values\BaseSkillValue;

class CharacterBuilder {

    private $race;

    private $class;

    private $character;

    public function setRace(GameRace $race): CharacterBuilder {
        $this->race = $race;

        return $this;
    }

    public function setClass(GameClass $class): CharacterBuilder {
        $this->class = $class;

        return $this;
    }

    public function createCharacter(User $user, string $name): CharacterBuilder {
        $baseStat = resolve(BaseStatValue::class)->setRace($this->race)->setClass($this->class);

        $this->character = Character::create([
            'user_id'       => $user->id,
            'game_race_id'  => $this->race->id,
            'game_class_id' => $this->class->id,
            'name'          => $name,
            'damage_stat'   => $this->class->damage_stat,
            'xp'            => 0,
            'xp_next'       => 100,
            'str'           => $baseStat->str(),
            'dur'           => $baseStat->dur(),
            'dex'           => $baseStat->dex(),
            'chr'           => $baseStat->chr(),
            'int'           => $baseStat->int(),
            'ac'            => $baseStat->ac(),
        ]);

        $this->character->inventory()->create([
            'character_id' => $this->character->id
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => Item::where('name', '=', 'Rusty Dagger')->first()->id,
        ]);

        $this->character->map()->create([
            'character_id' => $this->character->id
        ]);

        $this->character->equippedItems()->create([
            'character_id' => $this->character->id,
            'item_id'      => Item::where('name', '=', 'Rusty Dagger')->first()->id,
            'type'         => 'left-hand',
        ]);

        return $this;
    }

    public function assignSkills(): CharacterBuilder {
        foreach (config('game.skill_names') as $name) {
            $this->character->skills()->create(
                resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($this->character, $name)
            );
        }

        return $this;
    }

    public function character(): Character {
        return $this->character;
    }
}
