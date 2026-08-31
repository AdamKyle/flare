<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/items', ['uses' => 'Api\ItemsController@index']);
    Route::get('/admin/items/options', ['uses' => 'Api\ItemsController@options']);
    Route::post('/admin/items', ['uses' => 'Api\ItemsController@store']);
    Route::post('/admin/items/import', ['uses' => 'Api\ItemImportController']);
    Route::get('/admin/items/{item}/edit', ['uses' => 'Api\ItemsController@edit']);
    Route::get('/admin/items/{item}/usage', ['uses' => 'Api\ItemsController@usage']);
    Route::put('/admin/items/{item}', ['uses' => 'Api\ItemsController@update']);
    Route::delete('/admin/items/{item}', ['uses' => 'Api\ItemsController@destroy']);
    Route::get('/admin/items/{item}', ['uses' => 'Api\ItemsController@show']);
});
