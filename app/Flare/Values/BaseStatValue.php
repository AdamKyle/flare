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
        $modifier = $this->race->str_mod + $this->class->str_mod;

        return round(10 + $modifier);
    }

    public function dex() {
        $modifier = $this->race->dex_mod + $this->class->dex_mod;

        return round(10 + $modifier);
    }

    public function dur() {
        $modifier = $this->race->dur_mod + $this->class->dur_mod;

        return round(10 + $modifier);
    }

    public function chr() {
        $modifier = $this->race->chr_mod + $this->class->chr_mod;
        
        return round(10 + $modifier);
    }

    public function int() {
        $modifier = $this->race->int_mod + $this->class->int_mod;

        return round(10 + $modifier);
    }

    public function ac() {
        $modifier = $this->race->deffense_mod + $this->class->deffense_mod;

        return round(10 * ($modifier < 1 ? (1 + $modifier) : $modifier ));
    }
}
