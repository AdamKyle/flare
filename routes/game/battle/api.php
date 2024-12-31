<?php

Route::middleware(['auth', 'is.character.who.they.say.they.are', 'throttle:150,2'])->group(function () {

    Route::get('/monsters/{character}', ['uses' => 'Api\BattleController@index']);

    Route::middleware(['is.character.exploring'])->group(function () {
        Route::middleware(['is.character.dead', 'throttle:fighting'])->group(function () {

            Route::get('/setup-monster-fight/{character}/{monster}', ['uses' => 'Api\BattleController@setupMonster']);
            Route::post('/monster-fight/{character}', ['uses' => 'Api\BattleController@fightMonster']);

            Route::get('/celestial-fight/{character}/{celestialFight}', ['uses' => 'Api\CelestialBattleController@fetchCelestialFight']);

            Route::post('/conjure/{character}', ['uses' => 'Api\CelestialBattleController@conjure']);
            Route::post('/attack-celestial/{character}/{celestialFight}', ['uses' => 'Api\CelestialBattleController@attack']);

            Route::get('/raid-fight-participation/{character}/{monster}', ['uses' => 'Api\RaidBattleController@fetchRaidMonster']);
            Route::post('/raid-fight/{character}/{monster}', ['uses' => 'Api\RaidBattleController@fightMonster']);

            Route::post('/faction-loyalty-bounty/{character}', ['uses' => 'Api\FactionLoyaltyBattleController@handleBountyTask']);
        });

        Route::get('/celestial-beings/{character}', ['uses' => 'Api\CelestialBattleController@celestialMonsters']);

        Route::post('/celestial-revive/{character}', ['uses' => 'Api\CelestialBattleController@revive']);
    });

    Route::middleware(['throttle:fighting', 'is.globally.timed.out', 'is.character.exploring'])->group(function () {
        Route::post('/battle-results/{character}', ['uses' => 'Api\BattleController@battleResults']);
    });

    Route::post('/battle-revive/{character}', ['uses' => 'Api\BattleController@revive']);
});
