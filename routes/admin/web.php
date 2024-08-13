<?php

Route::get('/affixes/{affix}', ['as' => 'game.affixes.affix', 'uses' => 'AffixesController@show']);
Route::get('/game/kingdoms/units/{gameUnit}', ['as' => 'game.units.unit', 'uses' => 'UnitsController@show']);
Route::get('/game/kingdoms/buildings/{building}', ['as' => 'game.buildings.building', 'uses' => 'BuildingsController@show']);
Route::get('/game/quests/{quest}', ['as' => 'game.quests.show', 'uses' => 'QuestsController@show']);
Route::get('/game/npcs/{npc}', ['as' => 'game.npcs.show', 'uses' => 'NpcsController@show']);

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin', ['as' => 'home', 'uses' => 'AdminController@home']);

    Route::get('/admin/chat-logs', ['as' => 'admin.chat-logs', 'uses' => 'AdminController@chatLogs']);

    Route::get('/admin/maps', ['as' => 'maps', 'uses' => 'MapsController@index']);
    Route::get('/admin/maps/upload', ['as' => 'maps.upload', 'uses' => 'MapsController@uploadMap']);
    Route::get('/admin/maps/{gameMap}', ['as' => 'map', 'uses' => 'MapsController@show']);
    Route::get('/admin/maps/{gameMap}/add-bonuses', ['as' => 'map.bonuses', 'uses' => 'MapsController@manageBonuses']);
    Route::get('/admin/manage-map-locations/{gameMap}', ['as' => 'map.manage-locations', 'uses' => 'MapsController@manageMapLocations']);
    Route::post('/admin/maps/process-upload', ['as' => 'upload.map', 'uses' => 'MapsController@upload']);
    Route::post('/admin/maps/{gameMap}/post-bonuses', ['as' => 'add.map.bonuses', 'uses' => 'MapsController@postBonuses']);

    Route::get('/admin/locations/export-locations', ['as' => 'locations.export', 'uses' => 'LocationsController@exportLocations']);
    Route::get('/admin/locations/import-locations', ['as' => 'locations.import', 'uses' => 'LocationsController@importLocations']);
    Route::post('/admin/locations/export-data', ['as' => 'locations.export-data', 'uses' => 'LocationsController@export']);
    Route::post('/admin/locations/import-data', ['as' => 'locations.import-data', 'uses' => 'LocationsController@importData']);

    Route::get('/admin/locations', ['as' => 'locations.list', 'uses' => 'LocationsController@index']);
    Route::get('/admin/locations/create', ['as' => 'locations.create', 'uses' => 'LocationsController@create']);
    Route::get('/admin/location/{location}', ['as' => 'locations.location', 'uses' => 'LocationsController@show']);
    Route::get('/admin/locations/{location}/edit', ['as' => 'location.edit', 'uses' => 'LocationsController@edit']);
    Route::post('/admin/locations/store', ['as' => 'locations.store', 'uses' => 'LocationsController@store']);

    Route::get('/admin/monsters/export-monsters', ['as' => 'monsters.export', 'uses' => 'MonstersController@exportItems']);
    Route::get('/admin/monsters/import-monsters', ['as' => 'monsters.import', 'uses' => 'MonstersController@importItems']);
    Route::post('/admin/monsters/export-data', ['as' => 'monsters.export-data', 'uses' => 'MonstersController@export']);
    Route::post('/admin/monsters/import-data', ['as' => 'monsters.import-data', 'uses' => 'MonstersController@importData']);

    Route::get('/admin/monsters', ['as' => 'monsters.list', 'uses' => 'MonstersController@index']);
    Route::get('/admin/monsters/create', ['as' => 'monsters.create', 'uses' => 'MonstersController@create']);
    Route::get('/admin/monsters/{monster}', ['as' => 'monsters.monster', 'uses' => 'MonstersController@show']);
    Route::get('/admin/monsters/{monster}/edit', ['as' => 'monster.edit', 'uses' => 'MonstersController@edit']);
    Route::post('/admin/monsters/store', ['as' => 'monster.store', 'uses' => 'MonstersController@store']);

    Route::get('/admin/items/export-items', ['as' => 'items.export', 'uses' => 'ItemsController@exportItems']);
    Route::get('/admin/items/import-items', ['as' => 'items.import', 'uses' => 'ItemsController@importItems']);
    Route::post('/admin/items/export-data', ['as' => 'items.export-data', 'uses' => 'ItemsController@export']);
    Route::post('/admin/items/import-data', ['as' => 'items.import-data', 'uses' => 'ItemsController@importData']);

    Route::get('/admin/items', ['as' => 'items.list', 'uses' => 'ItemsController@index']);
    Route::get('/admin/items/create', ['as' => 'items.create', 'uses' => 'ItemsController@create']);
    Route::get('/admin/items/{item}', ['as' => 'items.item', 'uses' => 'ItemsController@show']);
    Route::get('/admin/items/{item}/edit', ['as' => 'items.edit', 'uses' => 'ItemsController@edit']);
    Route::post('/admin/items/store', ['as' => 'item.store', 'uses' => 'ItemsController@store']);
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
    Route::post('/admin/affixes/store', ['as' => 'affixes.store', 'uses' => 'AffixesController@store']);
    Route::post('/admin/affixes/{affix}/delete', ['as' => 'affixes.delete', 'uses' => 'AffixesController@delete']);

    Route::get('/admin/users', ['as' => 'users.list', 'uses' => 'UsersController@index']);
    Route::get('/admin/user/{user}', ['as' => 'users.user', 'uses' => 'UsersController@show']);
    Route::post('/admin/user/{user}/silence-user', ['as' => 'user.silence', 'uses' => 'UsersController@silenceUser']);
    Route::post('/admin/users/{user}/ban-user', ['as' => 'ban.user', 'uses' => 'UsersController@banUser']);
    Route::post('/admin/users/{user}/un-ban-user', ['as' => 'unban.user', 'uses' => 'UsersController@unBanUser']);
    Route::post('/admin/users/{user}/ignore-unban-request', ['as' => 'user.ignore.unban.request', 'uses' => 'UsersController@ignoreUnBanRequest']);
    Route::post('/admin/users/{user}/force-name-change', ['as' => 'user.force.name.change', 'uses' => 'UsersController@forceNameChange']);

    Route::get('/admin/skills/export-skills', ['as' => 'skills.export', 'uses' => 'SkillsController@exportSkills']);
    Route::get('/admin/skills/import-skills', ['as' => 'skills.import', 'uses' => 'SkillsController@importSkills']);
    Route::post('/admin/skills/export-data', ['as' => 'skills.export-data', 'uses' => 'SkillsController@export']);
    Route::post('/admin/skills/import-data', ['as' => 'skills.import-data', 'uses' => 'SkillsController@importData']);

    Route::post('/admin/skills/store', ['as' => 'skills.store', 'uses' => 'SkillsController@store']);
    Route::get('/admin/skills', ['as' => 'skills.list', 'uses' => 'SkillsController@index']);
    Route::get('/admin/skill/{skill}', ['as' => 'skills.skill', 'uses' => 'SkillsController@show']);
    Route::get('/admin/skills/create', ['as' => 'skills.create', 'uses' => 'SkillsController@create']);
    Route::get('/admin/skill/{skill}/edit', ['as' => 'skill.edit', 'uses' => 'SkillsController@edit']);

    Route::get('/admin/passive-skills/export-passive-skills', ['as' => 'passive.skills.export', 'uses' => 'PassiveSkillsController@exportPassives']);
    Route::get('/admin/passive-skills/import-passive-skills', ['as' => 'passive.skills.import', 'uses' => 'PassiveSkillsController@importPassives']);
    Route::post('/admin/passive-skills/export-data', ['as' => 'passive.skills.export-data', 'uses' => 'PassiveSkillsController@export']);
    Route::post('/admin/passive-skills/import-data', ['as' => 'passive.skills.import-data', 'uses' => 'PassiveSkillsController@importData']);

    Route::get('/admin/passive-skills', ['as' => 'passive.skills.list', 'uses' => 'PassiveSkillsController@index']);
    Route::get('/admin/passive-skill/{passiveSkill}', ['as' => 'passive.skills.skill', 'uses' => 'PassiveSkillsController@show']);
    Route::get('/admin/passive-skills/create', ['as' => 'passive.skills.create', 'uses' => 'PassiveSkillsController@create']);
    Route::get('/admin/passive-skill/{passiveSkill}/edit', ['as' => 'passive.skill.edit', 'uses' => 'PassiveSkillsController@edit']);
    Route::post('/admin/passive-skills/store', ['as' => 'passive.skill.store', 'uses' => 'PassiveSkillsController@store']);
    Route::post('/admin/passive-skills/{passiveSkill}/update', ['as' => 'passive.skill.update', 'uses' => 'PassiveSkillsController@update']);

    Route::get('/admin/class-specials', ['as' => 'class-specials.list', 'uses' => 'ClassSpecialsController@index']);
    Route::get('/admin/class-special/{gameClassSpecial}', ['as' => 'class-specials.show', 'uses' => 'ClassSpecialsController@show']);
    Route::get('/admin/class-specials/create', ['as' => 'class-specials.create', 'uses' => 'ClassSpecialsController@create']);
    Route::get('/admin/class-specials/{gameClassSpecial}/edit', ['as' => 'class-specials.edit', 'uses' => 'ClassSpecialsController@edit']);
    Route::get('/admin/class-specials/export-class-specials', ['as' => 'class-specials.show-export', 'uses' => 'ClassSpecialsController@showExport']);
    Route::get('/admin/class-specials/import-class-specials', ['as' => 'class-specials.show-import', 'uses' => 'ClassSpecialsController@showImport']);
    Route::post('/admin/class-specials/export', ['as' => 'class-specials.export', 'uses' => 'ClassSpecialsController@export']);
    Route::post('/admin/class-specials/import', ['as' => 'class-specials.import', 'uses' => 'ClassSpecialsController@import']);
    Route::post('/admin/class-specials/store', ['as' => 'class-specials.store', 'uses' => 'ClassSpecialsController@store']);
    Route::post('/admin/class-specials/store', ['as' => 'class-specials.store', 'uses' => 'ClassSpecialsController@store']);

    Route::post('/admin/race/store', ['as' => 'races.store', 'uses' => 'RacesController@store']);

    Route::get('/admin/races', ['as' => 'races.list', 'uses' => 'RacesController@index']);

    Route::get('/adamin/races/export-races', ['as' => 'races.export-view', 'uses' => 'RacesController@exportRaces']);
    Route::get('/adamin/races/import-races', ['as' => 'races.import-view', 'uses' => 'RacesController@importRaces']);

    Route::post('/adamin/races/export', ['as' => 'races.export', 'uses' => 'RacesController@export']);
    Route::post('/adamin/races/import', ['as' => 'races.import', 'uses' => 'RacesController@import']);

    Route::get('/admin/races/create', ['as' => 'races.create', 'uses' => 'RacesController@create']);
    Route::get('/admin/races/{race}', ['as' => 'races.race', 'uses' => 'RacesController@show']);
    Route::get('/admin/races/{race}/edit', ['as' => 'races.edit', 'uses' => 'RacesController@edit']);

    Route::post('/admin/classes/store', ['as' => 'classes.store', 'uses' => 'ClassesController@store']);

    Route::get('/admin/classes', ['as' => 'classes.list', 'uses' => 'ClassesController@index']);
    Route::get('/admin/classes/export-classes', ['as' => 'classes.export-excel', 'uses' => 'ClassesController@exportClasses']);
    Route::get('/admin/classes/import-classes', ['as' => 'classes.import-excel', 'uses' => 'ClassesController@importClasses']);

    Route::post('/admin/classes/export-class-data', ['as' => 'classes.export-data', 'uses' => 'ClassesController@export']);
    Route::post('/admin/classes/import-class-data', ['as' => 'classes.import-data', 'uses' => 'ClassesController@import']);

    Route::get('/admin/classes/create', ['as' => 'classes.create', 'uses' => 'ClassesController@create']);
    Route::get('/admin/classes/{class}', ['as' => 'classes.class', 'uses' => 'ClassesController@show']);
    Route::get('/admin/classes/{class}/edit', ['as' => 'classes.edit', 'uses' => 'ClassesController@edit']);

    Route::get('/admin/kingdoms/buildings/create', ['as' => 'buildings.create', 'uses' => 'BuildingsController@create']);
    Route::get('/admin/kingdoms/buildings', ['as' => 'buildings.list', 'uses' => 'BuildingsController@index']);
    Route::get('/admin/kingdoms/buildings/{building}', ['as' => 'buildings.building', 'uses' => 'BuildingsController@show']);
    Route::get('/admin/kingdoms/buildings/edit/{building}', ['as' => 'buildings.edit', 'uses' => 'BuildingsController@edit']);
    Route::post('/admin/kingdoms/buildings/store', ['as' => 'buildings.store', 'uses' => 'BuildingsController@store']);

    Route::get('/admin/kingdoms/units/create', ['as' => 'units.create', 'uses' => 'UnitsController@create']);
    Route::get('/admin/kingdoms/units', ['as' => 'units.list', 'uses' => 'UnitsController@index']);
    Route::get('/admin/kingdoms/units/{gameUnit}', ['as' => 'units.unit', 'uses' => 'UnitsController@show']);
    Route::get('/admin/kingdoms/units/edit/{gameUnit}', ['as' => 'units.edit', 'uses' => 'UnitsController@edit']);
    Route::post('/amdin/kingdoms/units/store', ['as' => 'units.store', 'uses' => 'UnitsController@store']);

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
    Route::post('/admin/npc/store', ['as' => 'npc.store', 'uses' => 'NpcsController@store']);

    Route::get('/admin/quests/export-quests', ['as' => 'quests.export', 'uses' => 'QuestsController@exportQuests']);
    Route::get('/admin/quests/import-quests', ['as' => 'quests.import', 'uses' => 'QuestsController@importQuests']);
    Route::post('/admin/quests/export-data', ['as' => 'quests.export-data', 'uses' => 'QuestsController@export']);
    Route::post('/admin/quests/import-data', ['as' => 'quests.import-data', 'uses' => 'QuestsController@import']);

    Route::post('/admin/quests/store', ['as' => 'quest.store', 'uses' => 'QuestsController@store']);

    Route::get('/admin/quests/index', ['as' => 'quests.index', 'uses' => 'QuestsController@index']);
    Route::get('/admin/quests/create', ['as' => 'quests.create', 'uses' => 'QuestsController@create']);
    Route::get('/admin/quests/edit/{quest}', ['as' => 'quests.edit', 'uses' => 'QuestsController@edit']);
    Route::get('/admin/quests/{quest}', ['as' => 'quests.show', 'uses' => 'QuestsController@show']);

    Route::post('/admin/guide-quests/store', ['as' => 'admin.guide-quests.store', 'uses' => 'GuideQuestsController@store']);
    Route::post('/admin/guide-quests/{guideQuest}/delete', ['as' => 'admin.guide-quests.delete', 'uses' => 'GuideQuestsController@delete']);

    Route::post('/admin/guide-quests/export-data', ['as' => 'admin.guide-quests.export-data', 'uses' => 'GuideQuestsController@export']);
    Route::post('/admin/guide-quests/import-data', ['as' => 'admin.guide-quests.import-data', 'uses' => 'GuideQuestsController@import']);

    Route::get('/admin/guide-quests', ['as' => 'admin.guide-quests', 'uses' => 'GuideQuestsController@index']);
    Route::get('/admin/guide-quests/create', ['as' => 'admin.guide-quests.create', 'uses' => 'GuideQuestsController@create']);
    Route::get('/admin/guide-quests/edit/{guideQuest}', ['as' => 'admin.guide-quests.edit', 'uses' => 'GuideQuestsController@edit']);
    Route::get('/admin/guide-quests/show/{guideQuest}', ['as' => 'admin.guide-quests.show', 'uses' => 'GuideQuestsController@show']);
    Route::get('/admin/guide-quests/export', ['as' => 'admin.guide-quests.export', 'uses' => 'GuideQuestsController@exportGuideQuests']);
    Route::get('/admin/guide-quests/import', ['as' => 'admin.guide-quests.import', 'uses' => 'GuideQuestsController@importGuideQuests']);

    Route::post('/admin/information-management/export', ['as' => 'admin.info-management.export', 'uses' => 'InformationController@export']);
    Route::post('/admin/information-management/import', ['as' => 'admin.info-management.import', 'uses' => 'InformationController@import']);

    Route::get('/admin/information-management', ['as' => 'admin.info-management', 'uses' => 'InformationController@index']);
    Route::get('/admin/information-management/export-data', ['as' => 'admin.info-management.export-data', 'uses' => 'InformationController@exportInfo']);
    Route::get('/admin/information-management/import-data', ['as' => 'admin.info-management.import-data', 'uses' => 'InformationController@importInfo']);
    Route::get('/admin/information-management/create-page', ['as' => 'admin.info-management.create-page', 'uses' => 'InformationController@managePage']);
    Route::get('/admin/information-management/page/{infoPage}', ['as' => 'admin.info-management.page', 'uses' => 'InformationController@page']);
    Route::get('/admin/information-management/update-page/{infoPage}', ['as' => 'admin.info-management.up-page', 'uses' => 'InformationController@managePage']);

    Route::post('/admin/raids/export', ['as' => 'admin.raids.export', 'uses' => 'RaidsController@export']);
    Route::post('/admin/raid/import', ['as' => 'admin.raids.import', 'uses' => 'RaidsController@import']);

    Route::get('/admin/raids', ['as' => 'admin.raids.list', 'uses' => 'RaidsController@index']);
    Route::get('/admin/raids/export-data', ['as' => 'admin.raids.export-data', 'uses' => 'RaidsController@exportRaids']);
    Route::get('/admin/raids/import-data', ['as' => 'admin.raids.import-data', 'uses' => 'RaidsController@importRaids']);
    Route::get('/admin/raids/create', ['as' => 'admin.raids.create', 'uses' => 'RaidsController@create']);
    Route::get('/admin/raids/{raid}/edit', ['as' => 'admin.raids.edit', 'uses' => 'RaidsController@edit']);
    Route::get('/admin/raids/{raid}', ['as' => 'admin.raids.show', 'uses' => 'RaidsController@show']);
    Route::post('/admin/raids/store', ['as' => 'admin.raids.store', 'uses' => 'RaidsController@store']);

    Route::post('/admin/item-skills/export', ['as' => 'admin.items-skills.export', 'uses' => 'ItemSkillsController@export']);
    Route::post('/admin/item-skills/import', ['as' => 'admin.items-skills.import', 'uses' => 'ItemSkillsController@import']);

    Route::get('/admin/item-skills', ['as' => 'admin.items-skills.list', 'uses' => 'ItemSkillsController@index']);
    Route::get('/admin/item-skills/export-data', ['as' => 'admin.items-skills.export-data', 'uses' => 'ItemSkillsController@exportItemSkills']);
    Route::get('/admin/item-skills/import-data', ['as' => 'admin.items-skills.import-data', 'uses' => 'ItemSkillsController@importItemSkills']);
    Route::get('/admin/item-skills/create', ['as' => 'admin.items-skills.create', 'uses' => 'ItemSkillsController@create']);
    Route::get('/admin/item-skills/{itemSkill}/edit', ['as' => 'admin.items-skills.edit', 'uses' => 'ItemSkillsController@edit']);
    Route::get('/admin/item-skills/{itemSkill}', ['as' => 'admin.items-skills.show', 'uses' => 'ItemSkillsController@show']);
    Route::post('/admin/item-skills/store', ['as' => 'admin.item-skills.store', 'uses' => 'ItemSkillsController@store']);

    Route::get('/admin/statistics/dashboard', ['as' => 'admin.statistics', 'uses' => 'StatisticsController@index']);
    Route::get('/admin/events', ['as' => 'admin.events', 'uses' => 'EventScheduleController@index']);

    Route::get('/admin/events/export-data', ['as' => 'admin.events.export-data', 'uses' => 'EventsController@exportEvents']);
    Route::get('/admin/events/import-data', ['as' => 'admin.events.import-data', 'uses' => 'EventsController@importEvents']);

    Route::get('/admin/feedback/bugs', ['as' => 'admin.feedback.bugs', 'uses' => 'FeedbackController@bugs']);
    Route::get('/admin/feedback/bug/{bug}', ['as' => 'admin.feedback.bug', 'uses' => 'FeedbackController@bug']);
    Route::get('/admin/feedback/suggestions', ['as' => 'admin.feedback.suggestions', 'uses' => 'FeedbackController@suggestions']);
    Route::get('/admin/feedback/suggestion/{suggestion}', ['as' => 'admin.feedback.suggestion', 'uses' => 'FeedbackController@suggestion']);

    Route::get('/admin/survey-builder/create-survey', ['as' => 'admin.survey-builder.create-survey', 'uses' => 'SurveyBuilderController@createSurvey']);
});
