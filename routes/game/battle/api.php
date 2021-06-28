<?php

Route::middleware(['auth', 'is.character.who.they.say.they.are', 'throttle:25,2'])->group(function() {
    Route::get('/actions', ['uses' => 'Api\BattleController@index']);
    Route::get('/celestial-beings/{character}', ['uses' => 'Api\CelestialBattleController@celestialMonsters']);
    Route::get('/celestial-fight/{character}/{celestialFight}', ['uses' => 'Api\CelestialBattleController@fetchCelestialFight']);

    Route::middleware(['throttle:fighting', 'is.globally.timed.out'])->group(function() {
        Route::post('/battle-results/{character}', ['uses' => 'Api\BattleController@battleResults']);
    });

    Route::post('/battle-revive/{character}', ['uses' => 'Api\BattleController@revive']);
    Route::post('/celestial-revive/{character}', ['uses' => 'Api\CelestialBattleController@revive']);
    Route::post('/conjure/{character}', ['uses' => 'Api\CelestialBattleController@conjure']);
    Route::post('/attack-celestial/{character}/{celestialFight}', ['uses' => 'Api\CelestialBattleController@attack']);
});

