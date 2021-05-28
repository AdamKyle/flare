<?php

namespace App\Flare\Values;

use App\Flare\Models\ReleaseNote;

class GameVersionHelper {

    public static function version() {
        $releaseNotes = ReleaseNote::orderBy('id', 'desc')->first();

        if (is_null($releaseNotes)) {
            return 'a.b.c';
        }

        return $releaseNotes->version;
    }
}
