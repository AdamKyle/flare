<?php

Route::post('/kingdoms/{character}/settle', ['as' => 'kingdoms.settle', 'uses' => 'Api\KingdomsController@settle']);