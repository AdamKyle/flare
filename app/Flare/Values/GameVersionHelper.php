<?php

namespace App\Flare\Values;

use App\Flare\Models\ReleaseNote;

class GameVersionHelper {

    public static function version() {
        return ReleaseNote::orderBy('id', 'desc')->first()->version;
    }
}
