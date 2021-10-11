<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\AdventureFloorDescriptionsFactory;

class AdventureFloorDescriptions extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'adventure_id',
        'description',
    ];

    public function adventure() {
        return $this->belongsTo(Adventure::class);
    }

    protected static function newFactory() {
        return AdventureFloorDescriptionsFactory::new();
    }
}
