<?php

Route::middleware(['auth', 'is.character.who.they.say.they.are', 'throttle:25,2'])->group(function() {
    Route::get('/actions', ['uses' => 'Api\BattleController@index']);

    Route::middleware(['throttle:fighting', 'is.globally.timed.out'])->group(function() {
        Route::post('/battle-results/{character}', ['uses' => 'Api\BattleController@battleResults']);
    });

    Route::post('/battle-revive/{character}', ['uses' => 'Api\BattleController@revive']);
});

