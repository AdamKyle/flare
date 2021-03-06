<?php

Route::middleware(['auth', 'is.player.banned'])->group(function() {
    Route::get('/adeventures/{adventure}', ['as' => 'map.adventures.adventure', 'uses' => 'AdventuresController@show']);
});