<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapitalCityResourceRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kingdom_requesting_id',
        'request_from_kingdom_id',
        'resources',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'resources' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function requestingKingdom()
    {
        return $this->belongsTo(Kingdom::class, 'kingdom_requesting_id', 'id');
    }

    public function requestingFromKingdom()
    {
        return $this->belongsTo(Kingdom::class, 'request_from_kingdom_id', 'id');
    }
}
