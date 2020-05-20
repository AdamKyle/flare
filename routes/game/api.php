<?php

Route::get('/calculate/item/comparison', ['as' => 'calculate.item.comparison', 'uses' => 'Api\ItemComparisonController@compare']);
