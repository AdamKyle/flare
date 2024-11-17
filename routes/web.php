<?php

Route::get('/', ['as' => 'welcome', 'uses' => 'WelcomeController@welcome']);
Route::get('/game-event-info', ['as' => 'event.type', 'uses' => 'WelcomeController@showEventPage']);
Route::get('/game-event-calendar', ['as' => 'event.calendar', 'uses' => 'WelcomeController@showEventCalendar']);

Route::get('/un-ban-request', ['as' => 'un.ban.request', 'uses' => 'UnbanRequestController@unbanRequest']);
Route::get('/un-ban/request-form/{user}', ['as' => 'un.ban.request.form', 'uses' => 'UnbanRequestController@requestForm']);
Route::post('/request-email', ['as' => 'un.ban.request.email', 'uses' => 'UnbanRequestController@findUser']);
Route::post('/request-submit/{user}', ['as' => 'un.ban.request.submit', 'uses' => 'UnbanRequestController@submitRequest']);

Route::group(['middleware' => 'update.player-activity'], function () {
    Route::get('/information/search', ['as' => 'info.search', 'uses' => 'InfoPageController@search']);
    Route::get('/information/{pageName}', ['as' => 'info.page', 'uses' => 'InfoPageController@viewPage']);
    Route::get('/information/race/{race}', ['as' => 'info.page.race', 'uses' => 'InfoPageController@viewRace']);
    Route::get('/information/class/{class}', ['as' => 'info.page.class', 'uses' => 'InfoPageController@viewClass']);
    Route::get('/information/skill/{skill}', ['as' => 'info.page.skill', 'uses' => 'InfoPageController@viewSkill']);
    Route::get('/information/monster/{monster}', ['as' => 'info.page.monster', 'uses' => 'InfoPageController@viewMonster']);
    Route::get('/information/locations/{location}', ['as' => 'info.page.location', 'uses' => 'InfoPageController@viewLocation']);
    Route::get('/information/building/{building}', ['as' => 'info.page.building', 'uses' => 'InfoPageController@viewBuilding']);
    Route::get('/information/unit/{unit}', ['as' => 'info.page.unit', 'uses' => 'InfoPageController@viewUnit']);
    Route::get('/information/item/{item}', ['as' => 'info.page.item', 'uses' => 'InfoPageController@viewItem']);
    Route::get('/information/affix/{affix}', ['as' => 'info.page.affix', 'uses' => 'InfoPageController@viewAffix']);
    Route::get('/information/map/{map}', ['as' => 'info.page.map', 'uses' => 'InfoPageController@viewMap']);
    Route::get('/information/npcs/{npc}', ['as' => 'info.page.npc', 'uses' => 'InfoPageController@viewNpc']);
    Route::get('/information/quests/{quest}', ['as' => 'info.page.quest', 'uses' => 'InfoPageController@viewQuest']);
    Route::get('/information/passive-skill/{passiveSkill}', ['as' => 'info.page.passive.skill', 'uses' => 'InfoPageController@viewPassiveSkill']);
    Route::get('/information/class-specials/{gameClassSpecial}', ['as' => 'info.page.class-special', 'uses' => 'InfoPageController@viewClassSpecialty']);
    Route::get('/information/raids/{raid}', ['as' => 'info.page.raid', 'uses' => 'InfoPageController@viewRaid']);
    Route::get('/information/item-skills/skill/{itemSkill}', ['as' => 'info.page.item-skill.skill', 'uses' => 'InfoPageController@itemSkill']);

    Route::get('/releases', ['as' => 'releases.list', 'uses' => 'ReleasesController@index']);
    Route::get('/features', ['as' => 'game.features', 'uses' => 'MarketingPagesController@features']);
    Route::get('/whos-playing', ['as' => 'game.whos-playing', 'uses' => 'MarketingPagesController@whosPlaying']);

    Route::post('/delete-account/{user}', ['as' => 'delete.account', 'uses' => 'AccountDeletionController@deleteAccount']);
    Route::post('/reset-account/{user}', ['as' => 'reset.account', 'uses' => 'AccountDeletionController@resetAccount']);

    Route::post('/survey-responses', ['as' => 'survey.question-response', 'uses' => 'SurveyStatsController@getResponseDataForQuestion']);
    Route::get('/survey-stats', ['as' => 'survey.stats', 'uses' => 'SurveyStatsController@getLatestSurveyData']);
    Route::get('/survey-stats/creators-response', ['as' => 'survey.creator-response', 'uses' => 'SurveyStatsController@getCreatorResponse']);
});

Auth::routes();
