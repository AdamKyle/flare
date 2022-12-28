<?php

Route::middleware(['auth', 'is.player.banned', 'is.character.who.they.say.they.are'])->group(function() {

    Route::get('/character/skills/{character}', ['uses' => 'Api\SkillsController@fetchSkills']);
    Route::get('/character/skill/{character}/{skill}', ['uses' => 'Api\SkillsController@skillInformation']);

    Route::middleware([ 'is.character.dead'])->group(function() {
        // Fetch Items
        Route::get('/crafting/{character}', ['uses' => 'Api\CraftingController@fetchItemsToCraft']);

        // Fetch Item Affixes
        Route::get('/enchanting/{character}', ['uses' => 'Api\EnchantingController@fetchAffixes']);

        // Fetch Alchemy Items
        Route::get('/alchemy/{character}', ['uses' => 'Api\AlchemyController@alchemyItems']);

        // Fetch Trinkets
        Route::get('/trinket-crafting/{character}', ['uses' => 'Api\TrinketCraftingController@fetchItemsToCraft']);


        Route::middleware(['is.character.exploring'])->group(function() {
            // Handle Training a specific skill.
            Route::post('/skill/train/{character}', ['uses' => 'Api\SkillsController@train']);

            // Handle Canceling the train of that skill.
            Route::post('/skill/cancel-train/{character}/{skill}', ['uses' => 'Api\SkillsController@cancelTrain']);
        });


        Route::group(['middleware' => 'throttle:crafting'], function() {
            // Craft Item
            Route::post('/craft/{character}', ['uses' => 'Api\CraftingController@craft']);
        });

        Route::group(['middleware' => 'throttle:crafting'], function() {
            // Craft Item
            Route::post('/trinket-crafting/craft/{character}/{item}', ['uses' => 'Api\TrinketCraftingController@craftTrinket']);
        });

        Route::group(['middleware' => 'throttle:enchanting'], function() {
            // Enchant Item
            Route::post('/enchant/{character}', ['uses' => 'Api\EnchantingController@enchant']);
        });

        Route::group(['middleware' => 'throttle:25,1'], function() {
            // Enchant Item
            Route::post('/disenchant/{item}', ['uses' => 'Api\DisenchantingController@disenchant']);
            Route::post('/destroy/{item}', ['uses' => 'Api\DisenchantingController@destroy']);
        });

        Route::group(['middleware' => 'throttle:25,1'], function() {
            // Alchemy
            Route::post('/transmute/{character}', ['uses' => 'Api\AlchemyController@transmute']);
        });

        Route::group(['middleware' => 'throttle:25,1'], function() {
            // Trinket Crafting
            Route::post('/trinket-craft/{character}', ['uses' => 'Api\TrinketCraftingController@craftTrinket']);
        });
    });


});
