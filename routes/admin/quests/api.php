<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/quests/tree', ['uses' => 'Api\QuestsController@tree']);
    Route::get('/admin/quests/options', ['uses' => 'Api\QuestsController@options']);
    Route::post('/admin/quests', ['uses' => 'Api\QuestsController@store']);
    Route::post('/admin/quests/import', ['uses' => 'Api\QuestImportController']);
    Route::get('/admin/quests/{quest}/edit', ['uses' => 'Api\QuestsController@edit']);
    Route::put('/admin/quests/{quest}', ['uses' => 'Api\QuestsController@update']);
    Route::get('/admin/quests/{quest}', ['uses' => 'Api\QuestsController@show']);
});
