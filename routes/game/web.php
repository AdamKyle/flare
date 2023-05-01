<?php

Route::get('/items/{item}', ['as' => 'game.items.item', 'uses' => 'ItemsController@show']);
Route::get('/monsters/{monster}', ['as' => 'game.monsters.monster', 'uses' => 'MonstersController@show']);
Route::get('/locations/{location}', ['as' => 'game.locations.location', 'uses' => 'LocationsController@show']);

Route::middleware([
    'auth',
])->group(function() {
    Route::get('/game/tops', ['as' => 'game.tops', 'uses' => 'GameTopsController@tops']);
    Route::get('/game/tops/{character}', ['as' => 'game.tops.character.stats', 'uses' => 'GameTopsController@characterStats']);

    Route::get('/game/ranked-fights-tops', ['as' => 'game.ranked-fights-tops', 'uses' => 'GameTopsController@rankedFightsTops']);
});

Route::middleware([
    'is.player.banned',
    'is.character.who.they.say.they.are',
    'is.globally.timed.out',
])->group(function() {

    // Core routes for the game related stuff:
    Route::get('/game', ['as' => 'game', 'uses' => 'GameController@game']);

    Route::get('/game/character/sheet', ['as' => 'game.character.sheet', 'uses' => 'CharacterSheetController@index']);

    Route::get('/game/event-calendar', ['as' => 'game.event.calendar', 'uses' => 'EventCalendarController@index']);

    Route::middleware(['is.character.exploring'])->group(function() {
        Route::get('/game/character/inventory/compare/{character}', ['as' => 'game.inventory.compare', 'uses' => 'CharacterInventoryController@compare']);
        Route::get('/game/character/inventory/compare-items/{user}', ['as' => 'game.inventory.compare-items', 'uses' => 'CharacterInventoryController@compareItem']);
        Route::post('/game/equip/item/{character}', ['as' => 'game.equip.item', 'uses' => 'CharacterInventoryController@equipItem']);
    });

    Route::get('/settings/{user}', ['as' => 'user.settings', 'uses' => 'SettingsController@index']);
    Route::post('/settings/{user}/auto-disenchant', ['as' => 'user.settings.auto-disenchant', 'uses' => 'SettingsController@autoDisenchantSettings']);
    Route::post('/settings/{user}/disable-attack-pop-overs', ['as' => 'user.settings.disable-attack-pop-overs', 'uses' => 'SettingsController@disableAttackTypePopOvers']);
    Route::post('/settings/{user}/chat-settings', ['as' => 'user.settings.chat', 'uses' => 'SettingsController@chatSettings']);
    Route::post('/settings/{user}/character-name', ['as' => 'user.settings.character', 'uses' => 'SettingsController@characterSettings']);
    Route::post('/settings/{user}/enable-guide', ['as' => 'user.settings.enable-guide', 'uses' => 'SettingsController@guideSettings']);
});
