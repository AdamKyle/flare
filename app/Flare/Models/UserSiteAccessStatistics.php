<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class UserSiteAccessStatistics extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amount_signed_in',
        'amount_registered',
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount_signed_in'  => 'integer',
        'amount_registered' => 'integer',
    ];
}
