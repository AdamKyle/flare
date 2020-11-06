<?php

namespace App\Flare\Models\Traits;

trait WithSearch {

    public static function dataTableSearch($query) {
        return empty($query) ? static::query()
            : static::where('name', 'like', '%'.$query.'%');
    }
}
