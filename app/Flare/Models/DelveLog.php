<?php

namespace App\Flare\Models;

use Database\Factories\DelveLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelveLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'delve_exploration_id',
        'increased_enemy_strength',
        'pack_size',
        'outcome',
        'fight_data',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'increased_enemy_strength' => 'float',
        'pack_size' => 'integer',
        'fight_data' => 'array',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function delveExploration(): BelongsTo
    {
        return $this->belongsTo(DelveExploration::class);
    }

    protected static function newFactory()
    {
        return DelveLogFactory::new();
    }
}
