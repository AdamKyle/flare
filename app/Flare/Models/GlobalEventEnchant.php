<?php

namespace App\Flare\Models;

use Database\Factories\GlobalEventEnchantingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalEventEnchant extends Model
{
    use HasFactory;

    protected $table = 'event_goal_participation_enchants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'global_event_goal_id',
        'character_id',
        'enchants',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'global_event_goal_id' => 'integer',
        'character_id' => 'integer',
        'enchants' => 'integer',
    ];

    public function globalEventGoal(): BelongsTo
    {
        return $this->belongsTo(GlobalEventGoal::class, 'global_event_goal_id', 'id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    protected static function newFactory()
    {
        return GlobalEventEnchantingFactory::new();
    }
}
