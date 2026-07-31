<?php

Route::get('/items/{item}', ['as' => 'game.items.item', 'uses' => 'ItemsController@show']);
Route::get('/monsters/{monster}', ['as' => 'game.monsters.monster', 'uses' => 'MonstersController@show']);
Route::get('/locations/{location}', ['as' => 'game.locations.location', 'uses' => 'LocationsController@show']);

Route::middleware([
    'auth',
])->group(function () {
    Route::get('/game/tops', ['as' => 'game.tops', 'uses' => 'GameTopsController@tops']);
    Route::get('/game/tops/characters/{character}', ['as' => 'game.tops.character.profile', 'uses' => 'GameTopsController@characterStats']);
    Route::get('/game/tops/exploration', ['as' => 'game.tops.exploration', 'uses' => 'GameTopsController@exploration']);
    Route::get('/game/tops/delve', ['as' => 'game.tops.delve', 'uses' => 'GameTopsController@delve']);
    Route::get('/game/tops/faction-loyalty', ['as' => 'game.tops.faction-loyalty', 'uses' => 'GameTopsController@factionLoyalty']);
    Route::get('/game/tops/kingdoms', ['as' => 'game.tops.kingdoms', 'uses' => 'GameTopsController@kingdoms']);
    Route::get('/game/tops/{character}', ['as' => 'game.tops.character.stats', 'uses' => 'GameTopsController@oldCharacterStats'])->where('character', '[0-9]+');

    Route::get('/tlessa-donations', ['as' => 'tlessa.donations', 'uses' => 'GameDonationController@donationSection']);
});

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
    'is.globally.timed.out',
])->group(function () {

    // Core routes for the game related stuff:
    Route::get('/game', ['as' => 'game', 'uses' => 'GameController@game']);
    Route::get('/goblin-shop/{character}', ['as' => 'game.goblin-shop.shop', 'uses' => 'GameController@game']);

    Route::get('/game/character/sheet', ['as' => 'game.character.sheet', 'uses' => 'CharacterSheetController@index']);

    Route::get('/game/event-calendar', ['as' => 'game.event.calendar', 'uses' => 'EventCalendarController@index']);

    Route::get('/settings/{user}', ['as' => 'user.settings', 'uses' => 'SettingsController@index']);
    Route::post('/settings/{user}/auto-disenchant', ['as' => 'user.settings.auto-disenchant', 'uses' => 'SettingsController@autoDisenchantSettings']);
    Route::post('/settings/{user}/disable-attack-pop-overs', ['as' => 'user.settings.disable-attack-pop-overs', 'uses' => 'SettingsController@disableAttackTypePopOvers']);
    Route::post('/settings/{user}/chat-settings', ['as' => 'user.settings.chat', 'uses' => 'SettingsController@chatSettings']);
    Route::post('/settings/{user}/character-name', ['as' => 'user.settings.character', 'uses' => 'SettingsController@characterSettings']);
    Route::post('/settings/{user}/enable-guide', ['as' => 'user.settings.enable-guide', 'uses' => 'SettingsController@guideSettings']);
    Route::post('/settings/{user}/cosmetic-text', ['as' => 'user.settings.cosmetic-text', 'uses' => 'SettingsController@cosmeticText']);
    Route::post('/settings/{user}/cosmetic-name-tag', ['as' => 'user.settings.cosmetic-name-tag', 'uses' => 'SettingsController@cosmeticNametag']);
    Route::post('/settings/{user}/cosmetic-race-changer', ['as' => 'user.settings.cosmetic-race-changer', 'uses' => 'SettingsController@cosmeticRaceChanger']);
});
