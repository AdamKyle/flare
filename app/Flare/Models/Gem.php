<?php

namespace App\Flare\Models;

use App\Game\Gems\Values\GemTierValue;
use App\Game\Gems\Values\GemTypeValue;
use Database\Factories\GemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gem extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'tier',
        'primary_atonement_type',
        'secondary_atonement_type',
        'tertiary_atonement_type',
        'primary_atonement_amount',
        'secondary_atonement_amount',
        'tertiary_atonement_amount',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'tier'                       => 'integer',
        'primary_atonement_type'     => 'integer',
        'secondary_atonement_type'   => 'integer',
        'tertiary_atonement_type'    => 'integer',
        'primary_atonement_amount'   => 'float',
        'secondary_atonement_amount' => 'float',
        'tertiary_atonement_amount'  => 'float',
    ];

    public function primaryAtonement(): GemTypeValue {
        return new GemTypeValue($this->primary_atonement_type);
    }

    public function secondaryAtonementType(): GemTypeValue {
        return new GemTypeValue($this->secondary_atonement_type);
    }

    public function tertiaryAtonementType(): GemTypeValue {
        return new GemTypeValue($this->tertiary_atonement_type);
    }

    public function gemTier(): GemTierValue {
        return new GemTierValue($this->tier);
    }

    protected static function newFactory() {
        return GemFactory::new();
    }
}
