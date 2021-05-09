<?php

Route::middleware(['auth', 'is.player.banned'])->group(function() {
    Route::get('/adventures/{adventure}', ['as' => 'map.adventures.adventure', 'uses' => 'AdventuresController@show']);
});
