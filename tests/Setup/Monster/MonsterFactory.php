<?php

namespace Tests\Setup\Monster;

use App\Flare\Models\Monster;
use Tests\Traits\CreateMonster;

class MonsterFactory {

    use CreateMonster;

    private $monster;

    public function buildMonster(): MonsterFactory {
        $this->monster = $this->createMonster();

        return $this;
    }

    public function updateMonster(array $changes = []): MonsterFactory {
        $this->monster->update($changes);

        $this->monster = $this->monster->refresh();

        return $this;
    }

    public function getMonster(): Monster {
        return $this->monster->refresh();
    }
}