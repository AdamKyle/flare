<?php

Route::middleware(['auth'])->group(function () {
    Route::get('/game/tops/monthly-snapshots', ['uses' => 'Api\TopsMonthlySnapshotController@index']);
    Route::get('/game/tops/characters', ['uses' => 'Api\CharacterTopsController@index']);
    Route::get('/game/tops/characters/{character}/overview', ['uses' => 'Api\CharacterTopsController@overview']);
    Route::get('/game/tops/characters/{character}/stats', ['uses' => 'Api\CharacterTopsController@stats']);
    Route::get('/game/tops/characters/{character}/equipment', ['uses' => 'Api\CharacterTopsController@equipment']);
    Route::get('/game/tops/characters/{character}/skills', ['uses' => 'Api\CharacterTopsController@skills']);
    Route::get('/game/tops/characters/{character}/factions', ['uses' => 'Api\CharacterTopsController@factions']);
    Route::get('/game/tops/characters/{character}/reincarnation', ['uses' => 'Api\CharacterTopsController@reincarnation']);
    Route::get('/game/tops/characters/{character}/activity', ['uses' => 'Api\CharacterTopsController@activity']);
    Route::get('/game/tops/characters/{character}/quests', ['uses' => 'Api\CharacterTopsController@quests']);
    Route::get('/game/tops/characters/{character}/kingdoms', ['uses' => 'Api\CharacterTopsController@kingdoms']);
    Route::get('/game/tops/characters/{character}/analytics', ['uses' => 'Api\CharacterTopsController@analytics']);
    Route::get('/game/tops/exploration', ['uses' => 'Api\ExplorationTopsController@index']);
    Route::get('/game/tops/exploration/{explorationLog}', ['uses' => 'Api\ExplorationTopsController@show']);
    Route::get('/game/tops/delve', ['uses' => 'Api\DelveTopsController@index']);
    Route::get('/game/tops/delve/{delveExploration}', ['uses' => 'Api\DelveTopsController@show']);
    Route::get('/game/tops/faction-loyalty', ['uses' => 'Api\FactionLoyaltyTopsController@index']);
    Route::get('/game/tops/faction-loyalty/{character}', ['uses' => 'Api\FactionLoyaltyTopsController@show']);
    Route::get('/game/tops/kingdoms', ['uses' => 'Api\KingdomTopsController@index']);
    Route::get('/game/tops/kingdoms/{character}', ['uses' => 'Api\KingdomTopsController@show']);
});
