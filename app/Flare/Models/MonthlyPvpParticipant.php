<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\GameMap;
use Database\Factories\MapFactory;

class MonthlyPvpParticipant extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'attack_type',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    protected static function newFactory() {

    }
}
