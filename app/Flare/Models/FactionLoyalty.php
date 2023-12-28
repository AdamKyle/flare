<?php

namespace App\Flare\Models;

use Database\Factories\FactionLoyaltyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactionLoyalty extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faction_id',
        'character_id',
        'is_pledged',
    ];

    protected $casts = [
        'is_pledged' => 'boolean',
    ];

    public function faction() {
        return $this->belongsTo(Faction::class, 'faction_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function factionLoyaltyNpcs() {
        return $this->hasMany(FactionLoyaltyNpc::class, 'faction_loyalty_id', 'id');
    }

    protected static function newFactory() {
        return FactionLoyaltyFactory::new();
    }
}
