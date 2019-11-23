<?php

// Core routes for the game related stuff:
Route::get('/game', ['as' => 'game', 'uses' => 'GameController@game']);
