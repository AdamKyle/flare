<?php

namespace App\Flare\ServerFight\Monster;

class ServerMonster {

    private int $health;

    private array $monster;

    public function setHealth(int $health): ServerMonster {
        $this->health = $health;

        return $this;
    }

    public function setMonster(array $monster): ServerMonster {
        $this->monster = $monster;

        return $this;
    }

    public function canMonsterDevoidPlayer(float $devouringDarknessResistance): bool {
        $chance = $this->monster['devouring_darkness_chance'];

        if ($devouringDarknessResistance > $chance) {
            return false;
        }

        $chance -= $devouringDarknessResistance;

        if ($chance >= 1) {
            return true;
        }

        $roll = rand(1, 100);

        $dc = (100 - 100 * $chance);

        return $roll > $dc;
    }

    public function canMonsterVoidPlayer(float $devouringLightResistance): bool {
        $chance = $this->monster['devouring_light_chance'];

        if ($devouringLightResistance > $chance) {
            return false;
        }

        $chance -= $devouringLightResistance;

        if ($chance >= 1) {
            return true;
        }

        $roll = rand(1, 100);

        $dc = (100 - 100 * $chance);

        return $roll > $dc;
    }

    public function buildAttack(): int {
        $attackArray = explode('-', $this->monster['attack_range']);

        $attack = rand($attackArray[0], $attackArray[1]);

        $increasesHealthBy = $this->monster['increases_damage_by'];

        if (!is_null($increasesHealthBy)) {
            $attack = $attack + $attack * $increasesHealthBy;
        }

        return $attack;
    }

    public function getName() {
        return $this->monster['name'];
    }

    public function getMonsterStat(string $key): mixed {
        return $this->monster[$key];
    }

    public function getMonster(): array {
        return $this->monster;
    }

    public function getHealth(): int {
        return $this->health;
    }
}
