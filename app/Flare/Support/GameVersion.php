<?php

namespace App\Flare\Support;

use App\Flare\Models\ReleaseNote;

class GameVersion
{
    public static function version()
    {
        $releaseNotes = ReleaseNote::orderBy('release_date', 'desc')->first();

        if (is_null($releaseNotes)) {
            return 'a.b.c';
        }

        return $releaseNotes->version;
    }
}
