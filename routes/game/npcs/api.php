<?php

Route::middleware(['auth', 'is.character.who.they.say.they.are'])->group(function () {
    Route::get('/npc-details/{npc}', ['uses' => 'Api\NpcsController@show']);
});
