<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/classes', ['uses' => 'Api\ClassesController@index']);
    Route::get('/admin/classes/options', ['uses' => 'Api\ClassesController@options']);
    Route::post('/admin/classes', ['uses' => 'Api\ClassesController@store']);
    Route::post('/admin/classes/import', ['uses' => 'Api\ClassImportController']);
    Route::get('/admin/classes/{gameClass}/edit', ['uses' => 'Api\ClassesController@edit']);
    Route::put('/admin/classes/{gameClass}', ['uses' => 'Api\ClassesController@update']);
    Route::get('/admin/classes/{gameClass}', ['uses' => 'Api\ClassesController@show']);
});
