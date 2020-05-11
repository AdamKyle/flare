<?php

namespace Tests\Setup;

use App\User;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateSkill;

class CharacterSetup {

    use CreateCharacter, CreateSkill, CreateRace, CreateClass;

    private $character;

    public function setupCharacter(array $options, User $user): CharacterSetup {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $this->character = $this->createCharacter([
            'name' => 'Sample',
            'user_id' => $user->id,
            'level' => isset($options['level']) ? $options['level'] : 1,
            'xp' => isset($options['xp']) ? $options['xp'] : 0,
            'can_attack' => isset($options['can_attack']) ? $options['can_attack'] : true,
        ]);

        // Create Empty Inventory:
        $this->character->inventory()->create([
            'character_id' => $this->character->id,
        ]);

        return $this;
    }

    public function equipLeftHand(Item $item): CharacterSetup {
        $this->character->equippedItems()->create([
            'character_id' => $this->character->id,
            'item_id'      => $item->id,
            'position'     => 'left-hand',
        ]);

        return $this;
    }

    public function equipRightHand(Item $item): CharacterSetup {
        $this->character->equippedItems()->create([
            'character_id' => $this->character->id,
            'item_id'      => $item->id,
            'position'     => 'right-hand',
        ]);

        return $this;
    }

    public function setSkill(string $name, array $options): CharacterSetup {
        $this->createSkill([
            'character_id' => $this->character->id,
            'name' => $name,
            'level' => isset($options['looting_level']) ? $options['looting_level'] : 1,
            'skill_bonus' => isset($options['looting_bonus']) ? $options['looting_bonus'] : 0,
        ]);

        return $this;
    }

    public function getCharacter(): Character {
        return $this->character;
    }

}
