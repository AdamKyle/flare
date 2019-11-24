<?php

namespace App\Flare\Values;

use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;

class BaseStatValue {

    private $race;

    private $class;

    public function setRace(GameRace $race): BaseStatValue {
        $this->race = $race;

        return $this;
    }

    public function setClass(GameClass $class): BaseStatValue {
        $this->class = $class;

        return $this;
    }

    public function str() {
        return ($this->race->str_mod + $this->class->str_mod) + 5;
    }

    public function dex() {
        return ($this->race->dex_mod + $this->class->dex_mod) + 5;
    }

    public function dur() {
        return ($this->race->dur_mod + $this->class->dur_mod) + 5;
    }

    public function chr() {
        return ($this->race->chr_mod + $this->class->chr_mod) + 5;
    }

    public function int() {
        return ($this->race->int_mod + $this->class->int_mod) + 5;
    }

    public function ac() {
        return ($this->race->deffense_mod + $this->class->deffense_mod) + 10;
    }
}
