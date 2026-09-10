<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/monsters', ['uses' => 'Api\MonstersController@index']);
    Route::get('/admin/monsters/options', ['uses' => 'Api\MonstersController@options']);
    Route::post('/admin/monsters', ['uses' => 'Api\MonstersController@store']);
    Route::post('/admin/monsters/import', ['uses' => 'Api\MonsterImportController']);
    Route::get('/admin/monsters/{monster}/edit', ['uses' => 'Api\MonstersController@edit']);
    Route::put('/admin/monsters/{monster}', ['uses' => 'Api\MonstersController@update']);
    Route::get('/admin/monsters/{monster}/gem-effect-contexts', ['uses' => 'Api\MonstersController@gemEffectContexts']);
    Route::get('/admin/monsters/{monster}', ['uses' => 'Api\MonstersController@show']);
});
