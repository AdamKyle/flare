<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class InfoPage extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'page_name',
        'page_sections',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'page_sections' => 'array',
    ];
}
