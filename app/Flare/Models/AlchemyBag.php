<?php

namespace App\Flare\Models;

use Database\Factories\AlchemyBagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlchemyBag extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
    ];

    protected $casts = [
        'character_id' => 'integer',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(AlchemyBagSlot::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    protected static function newFactory(): AlchemyBagFactory
    {
        return AlchemyBagFactory::new();
    }
}
