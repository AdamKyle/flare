<?php

Route::get('/actions', ['uses' => 'Api\BattleController@index']);
Route::post('/battle-results/{character}', ['uses' => 'Api\BattleController@battleResults']);
