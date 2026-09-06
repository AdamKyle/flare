<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/map-gems', ['uses' => 'Api\MapGemsController@index']);
    Route::get('/admin/map-gems/options', ['uses' => 'Api\MapGemsController@options']);
    Route::post('/admin/map-gems', ['uses' => 'Api\MapGemsController@store']);
    Route::post('/admin/map-gems/import', ['uses' => 'Api\MapGemImportController']);
    Route::post('/admin/map-gems/roll-all', ['uses' => 'Api\MapGemsController@rollAll']);
    Route::get('/admin/map-gems/{gameMapGemParamter}/edit', ['uses' => 'Api\MapGemsController@edit']);
    Route::put('/admin/map-gems/{gameMapGemParamter}', ['uses' => 'Api\MapGemsController@update']);
    Route::post('/admin/map-gems/{gameMapGemParamter}/roll', ['uses' => 'Api\MapGemsController@roll']);
    Route::put('/admin/map-gems/{gameMapGemParamter}/rolls/{gem}/activate', ['uses' => 'Api\MapGemsController@activateRoll']);
    Route::get('/admin/map-gems/{gameMapGemParamter}', ['uses' => 'Api\MapGemsController@show']);
});
