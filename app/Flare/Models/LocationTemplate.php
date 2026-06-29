<?php

namespace App\Flare\Models;

use App\Flare\Values\LocationTemplateType;
use Database\Factories\LocationTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_port',
        'can_players_enter',
    ];

    protected $casts = [
        'is_port' => 'boolean',
        'can_players_enter' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (LocationTemplate $locationTemplate): void {
            $locationTemplate->is_port = $locationTemplate->type === LocationTemplateType::PORT->value;

            if (is_null($locationTemplate->can_players_enter)) {
                $locationTemplate->can_players_enter = true;
            }
        });
    }

    protected static function newFactory()
    {
        return LocationTemplateFactory::new();
    }
}
