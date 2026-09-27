<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/skills', ['uses' => 'Api\SkillsController@index']);
    Route::get('/admin/skills/options', ['uses' => 'Api\SkillsController@options']);
    Route::post('/admin/skills', ['uses' => 'Api\SkillsController@store']);
    Route::post('/admin/skills/import', ['uses' => 'Api\SkillImportController']);
    Route::get('/admin/skills/{gameSkill}/edit', ['uses' => 'Api\SkillsController@edit']);
    Route::put('/admin/skills/{gameSkill}', ['uses' => 'Api\SkillsController@update']);
    Route::get('/admin/skills/{gameSkill}', ['uses' => 'Api\SkillsController@show']);
});
