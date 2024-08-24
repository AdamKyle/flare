<?php

namespace App\Flare\Models;

use App\Game\Kingdoms\Values\BuildingQueueType;
use Database\Factories\BuildingInQueueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingInQueue extends Model
{
    use HasFactory;

    protected $table = 'buildings_in_queue';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'kingdom_id',
        'building_id',
        'to_level',
        'paid_with_gold',
        'paid_amount',
        'completed_at',
        'started_at',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'to_level' => 'integer',
        'paid_amount' => 'integer',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
        'paid_with_gold' => 'boolean',
        'type' => 'integer',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'type_name',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function building()
    {
        return $this->belongsTo(KingdomBuilding::class);
    }

    public function kingdom()
    {
        return $this->belongsTo(Kingdom::class);
    }

    public function getTypeNameAttribute()
    {
        return (new BuildingQueueType($this->type))->getNameOfType();
    }

    public function getType(): BuildingQueueType
    {
        return new BuildingQueueType($this->type);
    }

    protected static function newFactory()
    {
        return BuildingInQueueFactory::new();
    }
}
