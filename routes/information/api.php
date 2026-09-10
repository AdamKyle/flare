<?php

Route::get('/quests/tree', ['uses' => 'QuestsController@tree']);
Route::get('/quests/options', ['uses' => 'QuestsController@options']);
Route::get('/quests/{quest}', ['uses' => 'QuestsController@show']);
Route::get('/monsters/{monster}/gem-effect-contexts', ['uses' => 'MonstersController@gemEffectContexts']);
Route::get('/monsters/{monster}', ['uses' => 'MonstersController@show']);
