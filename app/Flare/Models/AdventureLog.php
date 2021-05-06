<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\AdventureLogFactory;

class AdventureLog extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'adventure_id',
        'complete',
        'in_progress',
        'took_to_long',
        'last_completed_level',
        'logs',
        'rewards',
        'created_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'complete'             => 'boolean',
        'in_progress'          => 'boolean',
        'took_to_long'         => 'boolean',
        'last_completed_level' => 'integer',
        'logs'                 => 'array',
        'rewards'              => 'array',
        'created_at'           => 'date',
    ];

    public function setLogsAttribute($value) {
        $this->attributes['logs'] = json_encode($value);
    }

    public function setRewardsAttribute($value) {
        $this->attributes['rewards'] = json_encode($value);
    }

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function adventure() {
        return $this->hasOne(Adventure::class, 'id', 'adventure_id');
    }

    protected static function newFactory() {
        return AdventureLogFactory::new();
    }
}
