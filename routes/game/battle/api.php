<?php

Route::middleware(['auth', 'is.character.who.they.say.they.are', 'throttle:150,2'])->group(function() {

    Route::get('/actions/{character}', ['uses' => 'Api\BattleController@index']);

    Route::middleware(['is.character.exploring'])->group(function() {
        Route::middleware(['is.character.dead'])->group(function() {
            Route::get('/celestial-fight/{character}/{celestialFight}', ['uses' => 'Api\CelestialBattleController@fetchCelestialFight']);

            Route::post('/conjure/{character}', ['uses' => 'Api\CelestialBattleController@conjure']);
            Route::post('/attack-celestial/{character}/{celestialFight}', ['uses' => 'Api\CelestialBattleController@attack']);

            Route::get('/attack-player/get-health/{character}', ['uses' => 'Api\PvpBattleController@getHealth']);
            Route::post('/attack-player/{character}', ['uses' => 'Api\PvpBattleController@fightCharacter']);

            Route::post('/join-monthly-pvp/{character}', ['uses' => 'Api\MonthlyPvpParticipantsController@join']);
        });

        Route::get('/celestial-beings/{character}', ['uses' => 'Api\CelestialBattleController@celestialMonsters']);

        Route::post('/celestial-revive/{character}', ['uses' => 'Api\CelestialBattleController@revive']);

        Route::post('/pvp/revive/{character}', ['uses' => 'Api\PvpBattleController@revive']);
    });

    Route::middleware(['throttle:fighting', 'is.globally.timed.out', 'is.character.exploring'])->group(function() {
        Route::post('/battle-results/{character}', ['uses' => 'Api\BattleController@battleResults']);
    });

    Route::post('/battle-revive/{character}', ['uses' => 'Api\BattleController@revive']);
});

