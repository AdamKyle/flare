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
        return round(1 * (1 + ($this->race->str_mod + $this->class->str_mod)));
    }

    public function dex() {
        return round(1 * (1 + ($this->race->dex_mod + $this->class->dex_mod)));
    }

    public function dur() {
        return round(1 * (1 + ($this->race->dur_mod + $this->class->dur_mod)));
    }

    public function chr() {
        return round(1 * (1 + ($this->race->chr_mod + $this->class->chr_mod)));
    }

    public function int() {
        return round(1 * (1 + ($this->race->int_mod + $this->class->int_mod)));
    }

    public function ac() {
        return round(10 * (1 + ($this->race->deffense_mod + $this->class->deffense_mod)));
    }
}
