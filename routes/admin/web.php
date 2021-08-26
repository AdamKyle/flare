<?php

Route::get('/affixes/{affix}', ['as' => 'game.affixes.affix', 'uses' => 'AffixesController@show']);
Route::get('/game/adventures/{adventure}', ['as' => 'game.adventures.adventure', 'uses' => 'AdventuresController@show']);
Route::get('/game/locations/{location}', ['as' => 'game.locations.location', 'uses' => 'Location@show']);
Route::get('/game/kingdoms/units/{gameUnit}', ['as' => 'game.units.unit', 'uses' => 'UnitsController@show']);
Route::get('/game/kingdoms/buildings/{building}', ['as' => 'game.buildings.building', 'uses' => 'BuildingsController@show']);
Route::get('/game/quests/{quest}', ['as' => 'game.quests.show', 'uses' => 'QuestsController@show']);
Route::get('/game/npcs/{npc}', ['as' => 'game.npcs.show', 'uses' => 'NpcsController@show']);

Route::middleware(['auth', 'is.admin'])->group(function() {
    Route::get('/admin', ['as' => 'home', 'uses' => 'AdminController@home']);
    Route::get('/admin/maps', ['as' => 'maps', 'uses' => 'MapsController@index']);
    Route::get('/admin/maps/{gameMap}/add-bonuses', ['as' => 'map.bonuses', 'uses' => 'MapsController@createBonuses']);
    Route::get('/admin/maps/{gameMap}/view-bonuses', ['as' => 'view.map.bonuses', 'uses' => 'MapsController@viewBonuses']);
    Route::get('/admin/maps/upload', ['as' => 'maps.upload', 'uses' => 'MapsController@uploadMap']);
    Route::post('/admin/maps/process-upload', ['as' => 'upload.map', 'uses' => 'MapsController@upload']);
    Route::post('/admin/maps/{gameMap}/post-bonuses', ['as' => 'add.map.bonuses', 'uses' => 'MapsController@postBonuses']);

    Route::get('/admin/locations', ['as' => 'locations.list', 'uses' => 'LocationsController@index']);
    Route::get('/admin/locations/create', ['as' => 'locations.create', 'uses' => 'LocationsController@create']);
    Route::get('/admin/location/{location}', ['as' => 'locations.location', 'uses' => 'LocationsController@show']);
    Route::get('/admin/locations/{location}/edit', ['as' => 'location.edit', 'uses' => 'LocationsController@edit']);

    Route::get('/admin/adventures', ['as' => 'adventures.list', 'uses' => 'AdventuresController@index']);
    Route::get('/admin/adventures/create', ['as' => 'adventures.create', 'uses' => 'AdventuresController@create']);
    Route::post('/admin/adventures/{adventure}/publish', ['as' => 'adventure.publish', 'uses' => 'AdventuresController@publish']);
    Route::get('/admin/adventures/{adventure}', ['as' => 'adventures.adventure', 'uses' => 'AdventuresController@show']);
    Route::get('/admin/adventures/{adventure}/edit', ['as' => 'adventure.edit', 'uses' => 'AdventuresController@edit']);
    Route::post('/admin/adventures/store', ['as' => 'adventures.store', 'uses' => 'AdventuresController@store']);
    Route::post('/admin/adventures/{adventure}/update', ['as' => 'adventure.update', 'uses' => 'AdventuresController@update']);

    Route::get('/admin/monsters/export-monsters', ['as' => 'monsters.export', 'uses' => 'MonstersController@exportItems']);
    Route::get('/admin/monsters/import-monsters', ['as' => 'monsters.import', 'uses' => 'MonstersController@importItems']);
    Route::post('/admin/monsters/export-data', ['as' => 'monsters.export-data', 'uses' => 'MonstersController@export']);
    Route::post('/admin/monsters/import-data', ['as' => 'monsters.import-data', 'uses' => 'MonstersController@importData']);

    Route::get('/admin/monsters', ['as' => 'monsters.list', 'uses' => 'MonstersController@index']);
    Route::get('/admin/monsters/create', ['as' => 'monsters.create', 'uses' => 'MonstersController@create']);
    Route::get('/admin/monsters/{monster}', ['as' => 'monsters.monster', 'uses' => 'MonstersController@show']);
    Route::get('/admin/monsters/{monster}/edit', ['as' => 'monster.edit', 'uses' => 'MonstersController@edit']);
    Route::post('/admin/monsters/{monster}/pblish', ['as' => 'monster.publish', 'uses' => 'MonstersController@publish']);

    Route::get('/admin/items/export-items', ['as' => 'items.export', 'uses' => 'ItemsController@exportItems']);
    Route::get('/admin/items/import-items', ['as' => 'items.import', 'uses' => 'ItemsController@importItems']);
    Route::post('/admin/items/export-data', ['as' => 'items.export-data', 'uses' => 'ItemsController@export']);
    Route::post('/admin/items/import-data', ['as' => 'items.import-data', 'uses' => 'ItemsController@importData']);

    Route::get('/admin/items', ['as' => 'items.list', 'uses' => 'ItemsController@index']);
    Route::get('/admin/items/create', ['as' => 'items.create', 'uses' => 'ItemsController@create']);
    Route::get('/admin/items/{item}', ['as' => 'items.item', 'uses' => 'ItemsController@show']);
    Route::get('/admin/items/{item}/edit', ['as' => 'items.edit', 'uses' => 'ItemsController@edit']);
    Route::post('/admin/items/{item}/delete', ['as' => 'items.delete', 'uses' => 'ItemsController@delete']);
    Route::post('/admin/items/delete-all', ['as' => 'items.delete.all', 'uses' => 'ItemsController@deleteAll']);

    Route::get('/admin/affixes/export-affixes', ['as' => 'affixes.export', 'uses' => 'AffixesController@exportItems']);
    Route::get('/admin/affixes/import-affixes', ['as' => 'affixes.import', 'uses' => 'AffixesController@importItems']);
    Route::post('/admin/affixes/export-data', ['as' => 'affixes.export-data', 'uses' => 'AffixesController@export']);
    Route::post('/admin/affixes/import-data', ['as' => 'affixes.import-data', 'uses' => 'AffixesController@importData']);

    Route::get('/admin/affixes', ['as' => 'affixes.list', 'uses' => 'AffixesController@index']);
    Route::get('/admin/affixes/create', ['as' => 'affixes.create', 'uses' => 'AffixesController@create']);
    Route::get('/admin/affixes/{affix}', ['as' => 'affixes.affix', 'uses' => 'AffixesController@show']);
    Route::get('/admin/affixes/{affix}/edit', ['as' => 'affixes.edit', 'uses' => 'AffixesController@edit']);
    Route::post('/admin/affixes/{affix}/delete', ['as' => 'affixes.delete', 'uses' => 'AffixesController@delete']);

    Route::get('/admin/users', ['as' => 'users.list', 'uses' => 'UsersController@index']);
    Route::get('/admin/user/{user}', ['as' => 'users.user', 'uses' => 'UsersController@show']);
    Route::get('/admin/user/ban-reason/{user}/{for}', ['as' => 'ban.reason', 'uses' => 'UsersController@banReason']);
    Route::post('/admin/user/{user}/reset-password', ['as' => 'user.reset.password', 'uses' => 'UsersController@resetPassword']);
    Route::post('/admin/user/{user}/silence-user', ['as' => 'user.silence', 'uses' => 'UsersController@silenceUser']);
    Route::post('/admin/users/{user}/ban-user', ['as' => 'ban.user', 'uses' => 'UsersController@banUser']);
    Route::post('/admin/users/{user}/un-ban-user', ['as' => 'unban.user', 'uses' => 'UsersController@unBanUser']);
    Route::post('/admin/users/{user}/submit-reason', ['as' => 'ban.user.with.reason', 'uses' => 'UsersController@submitBanReason']);
    Route::post('/admin/users/{user}/ingore-unban-request', ['as' => 'user.ignore.unban.request', 'uses' => 'UsersController@ignoreUnBanRequest']);
    Route::post('/admin/users/{user}/force-name-change', ['as' => 'user.force.name.change', 'uses' => 'UsersController@forceNameChange']);

    Route::get('/admin/skills/export-skills', ['as' => 'skills.export', 'uses' => 'SkillsController@exportSkills']);
    Route::get('/admin/skills/import-skills', ['as' => 'skills.import', 'uses' => 'SkillsController@importSkills']);
    Route::post('/admin/skills/export-data', ['as' => 'skills.export-data', 'uses' => 'SkillsController@export']);
    Route::post('/admin/skills/import-data', ['as' => 'skills.import-data', 'uses' => 'SkillsController@importData']);

    Route::get('/admin/skills', ['as' => 'skills.list', 'uses' => 'SkillsController@index']);
    Route::get('/admin/skill/{skill}', ['as' => 'skills.skill', 'uses' => 'SkillsController@show']);
    Route::get('/admin/skills/create', ['as' => 'skills.create', 'uses' => 'SkillsController@create']);
    Route::get('/admin/skill/{skill}/edit', ['as' => 'skill.edit', 'uses' => 'SkillsController@edit']);

    Route::get('/admin/races', ['as' => 'races.list', 'uses' => 'RacesController@index']);
    Route::get('/admin/races/create', ['as' => 'races.create', 'uses' => 'RacesController@create']);
    Route::get('/admin/races/{race}', ['as' => 'races.race', 'uses' => 'RacesController@show']);
    Route::get('/admin/races/{race}/edit', ['as' => 'races.edit', 'uses' => 'RacesController@edit']);

    Route::get('/admin/classes', ['as' => 'classes.list', 'uses' => 'ClassesController@index']);
    Route::get('/admin/classes/create', ['as' => 'classes.create', 'uses' => 'ClassesController@create']);
    Route::get('/admin/classes/{class}', ['as' => 'classes.class', 'uses' => 'ClassesController@show']);
    Route::get('/admin/classes/{class}/edit', ['as' => 'classes.edit', 'uses' => 'ClassesController@edit']);

    Route::get('/admin/kingdoms/buildings/create', ['as' => 'buildings.create', 'uses' => 'BuildingsController@create']);
    Route::get('/admin/kingdoms/buildings', ['as' => 'buildings.list', 'uses' => 'BuildingsController@index']);
    Route::get('/admin/kingdoms/buildings/{building}', ['as' => 'buildings.building', 'uses' => 'BuildingsController@show']);
    Route::get('/admin/kingdoms/buildings/edit/{building}', ['as' => 'buildings.edit', 'uses' => 'BuildingsController@edit']);

    Route::get('/admin/kingdoms/units/create', ['as' => 'units.create', 'uses' => 'UnitsController@create']);
    Route::get('/admin/kingdoms/units', ['as' => 'units.list', 'uses' => 'UnitsController@index']);
    Route::get('/admin/kingdoms/units/{gameUnit}', ['as' => 'units.unit', 'uses' => 'UnitsController@show']);
    Route::get('/admin/kingdoms/units/edit/{gameUnit}', ['as' => 'units.edit', 'uses' => 'UnitsController@edit']);

    Route::get('/admin/kingdoms/export', ['as' => 'kingdoms.export', 'uses' => 'KingdomsController@index']);
    Route::get('/admin/kingdoms/import', ['as' => 'kingdoms.import', 'uses' => 'KingdomsController@import']);
    Route::post('/admin/kingdoms/export-data', ['as' => 'kingdoms.export-data', 'uses' => 'KingdomsController@export']);
    Route::post('/admin/kingdoms/import-data', ['as' => 'kingdoms.import-data', 'uses' => 'KingdomsController@importData']);

    Route::get('/admin/npcs/export-npcs', ['as' => 'npcs.export', 'uses' => 'NpcsController@exportNpcs']);
    Route::get('/admin/npcs/import-npcs', ['as' => 'npcs.import', 'uses' => 'NpcsController@importNpcs']);
    Route::post('/admin/npcs/export-data', ['as' => 'npcs.export-data', 'uses' => 'NpcsController@export']);
    Route::post('/admin/npcs/import-data', ['as' => 'npcs.import-data', 'uses' => 'NpcsController@import']);

    Route::get('/admin/npcs/index', ['as' => 'npcs.index', 'uses' => 'NpcsController@index']);
    Route::get('/admin/npcs/create', ['as' => 'npcs.create', 'uses' => 'NpcsController@create']);
    Route::get('/admin/npcs/edit/{npc}', ['as' => 'npcs.edit', 'uses' => 'NpcsController@edit']);
    Route::get('/admin/npcs/{npc}', ['as' => 'npcs.show', 'uses' => 'NpcsController@show']);

    Route::get('/admin/quests/export-quests', ['as' => 'quests.export', 'uses' => 'QuestsController@exportQuests']);
    Route::get('/admin/quests/import-quests', ['as' => 'quests.import', 'uses' => 'QuestsController@importQuests']);
    Route::post('/admin/quests/export-data', ['as' => 'quests.export-data', 'uses' => 'QuestsController@export']);
    Route::post('/admin/quests/import-data', ['as' => 'quests.import-data', 'uses' => 'QuestsController@import']);

    Route::get('/admin/quests/index', ['as' => 'quests.index', 'uses' => 'QuestsController@index']);
    Route::get('/admin/quests/create', ['as' => 'quests.create', 'uses' => 'QuestsController@create']);
    Route::get('/admin/quests/edit/{quest}', ['as' => 'quests.edit', 'uses' => 'QuestsController@edit']);
    Route::get('/admin/quests/{quest}', ['as' => 'quests.show', 'uses' => 'QuestsController@show']);

    Route::get('/admin/statistics/dashboard', ['as' => 'admin.statistics', 'uses' => 'StatisticsController@index']);
});
