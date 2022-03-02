<?php

namespace App\Flare\Models;

use Database\Factories\UserSiteAccessStatisticsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSiteAccessStatistics extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amount_signed_in',
        'amount_registered',
        'invalid_ips',
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount_signed_in'  => 'integer',
        'amount_registered' => 'integer',
        'invalid_ips'       => 'array',
    ];

    protected static function newFactory() {
        return UserSiteAccessStatisticsFactory::new();
    }
}
