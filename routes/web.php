<?php

Route::get('/', function () {
    if (!Auth::check()) {
        return view('welcome');
    }

    if (auth()->user()->hasRole('Admin')) {
        return redirect()->route('home');
    }

    return redirect()->route('game');
});

Route::get('/information/{pageName}', ['as' => 'info.page', 'uses' => 'InfoPageController@viewPage']);
Route::get('/information/race/{race}', ['as' => 'info.page.race', 'uses' => 'InfoPageController@viewRace']);
Route::get('/information/class/{class}', ['as' => 'info.page.class', 'uses' => 'InfoPageController@viewClass']);
Route::get('/information/skill/{skill}', ['as' => 'info.page.skill', 'uses' => 'InfoPageController@viewSkill']);
Route::get('/information/adventure/{adventure}', ['as' => 'info.page.adventure', 'uses' => 'InfoPageController@viewAdventure']);
Route::get('/information/monster/{monster}', ['as' => 'info.page.monster', 'uses' => 'InfoPageController@viewMonster']);
Route::get('/information/locations/{location}', ['as' => 'info.page.location', 'uses' => 'InfoPageController@viewLocation']);
Route::get('/information/unit/{unit}', ['as' => 'info.page.unit', 'uses' => 'InfoPageController@viewUnit']);
Route::get('/information/item/{item}', ['as' => 'info.page.item', 'uses' => 'InfoPageController@viewItem']);
Route::get('/information/affix/{affix}', ['as' => 'info.page.affix', 'uses' => 'InfoPageController@viewAffix']);
Route::get('/information/map/{map}', ['as' => 'info.page.map', 'uses' => 'InfoPageController@viewMap']);
Route::get('/information/npcs/{npc}', ['as' => 'information.npcs.npc', 'uses' => 'InfoPageController@viewNpc']);
Route::get('/information/quests/{quest}', ['as' => 'information.quests.quest', 'uses' => 'InfoPageController@viewQuest']);

Route::get('/releases', ['as' => 'releases.list', 'uses' => 'ReleasesController@index']);


Route::get('/un-ban-request', ['as' => 'un.ban.request', 'uses' => 'UnbanRequestController@unbanRequest']);
Route::get('/un-ban/request-form/{user}', ['as' => 'un.ban.request.form', 'uses' => 'UnbanRequestController@requestForm']);
Route::post('/request-email', ['as' => 'un.ban.request.email', 'uses' => 'UnbanRequestController@findUser']);
Route::post('/request-submit/{user}', ['as' => 'un.ban.request.submit', 'uses' => 'UnbanRequestController@submitRequest']);

Route::post('/delete-account/{user}', ['as' => 'delete.account', 'uses' => 'AccountDeletionController@deleteAccount']);

Auth::routes();
